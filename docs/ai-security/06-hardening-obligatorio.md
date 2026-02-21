# 06 — Hardening Obligatorio: Rate Limiting, Guard de Conversación y Consistencia de Enums

## Objetivo

Cerrar tres brechas de seguridad detectadas tras la implementación de las estrategias 01–05. Son cambios pequeños pero obligatorios porque cubren vectores de ataque accesibles.

---

## 1. Rate limiting en el endpoint de mensajes

### Problema

No hay límite de mensajes por usuario. Un atacante puede enviar cientos de mensajes en segundos, saturando:
- La cola de `ProcessConversationJob` (consumo de API del LLM)
- La cola de `ModerateMessageJob` (más consumo de API)
- Recursos del servidor (jobs acumulados)

### Solución

Aplicar rate limiting con `RateLimiter` de Laravel al grupo de rutas `atomic-ia`.

### Implementación

#### `bootstrap/app.php` — registrar el rate limiter

Añadir un nuevo `RateLimiter` en `AppServiceProvider::boot()` y aplicarlo como middleware en las rutas.

```php
// app/Providers/AppServiceProvider.php — dentro de boot()

RateLimiter::for('atomic-ia', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

#### `routes/backoffice.php` — aplicar al store y newConversation

```php
Route::jsonGroup('atomic-ia', AtomicIAController::class, [
    'index', 'json', 'store',
]);

Route::post('atomic-ia/conversations', [AtomicIAController::class, 'newConversation'])
    ->name('atomic-ia.new-conversation');
```

El rate limiter se aplica solo a `store` y `newConversation` (las acciones de escritura), no a `index` ni `json` (lectura).

Opción A — middleware en el controller:

```php
// app/Http/Controllers/Backoffice/AtomicIAController.php

public function __construct()
{
    $this->middleware('throttle:atomic-ia')->only(['store', 'newConversation']);
}
```

Opción B — middleware directo en las rutas:

```php
// routes/backoffice.php

Route::jsonGroup('atomic-ia', AtomicIAController::class, [
    'index', 'json',
]);

Route::middleware('throttle:atomic-ia')->group(function () {
    Route::post('atomic-ia', [AtomicIAController::class, 'store'])
        ->name('atomic-ia.store');

    Route::post('atomic-ia/conversations', [AtomicIAController::class, 'newConversation'])
        ->name('atomic-ia.new-conversation');
});
```

> **Nota**: La opción B requiere sacar `store` del `jsonGroup` y definirlo manualmente. Evaluar cuál se alinea mejor con la convención del proyecto.

### Valores recomendados

| Límite | Valor | Razón |
|--------|-------|-------|
| Mensajes por minuto | 10 | Conversación normal no supera 3–5 msg/min |
| Conversaciones por minuto | 5 | Crear conversaciones es infrecuente |

---

## 2. Guard de conversación en `ProcessConversationJob`

### Problema

`ProcessConversationJob` no verifica el status de la conversación antes de procesarla. Si un usuario envía un mensaje justo antes de que la conversación sea baneada, el job ejecuta la llamada al LLM igualmente — desperdiciando recursos y potencialmente procesando un contexto comprometido.

### Solución

Early return en `handle()` si la conversación no está `Active`.

### Implementación

```php
// app/Jobs/ProcessConversationJob.php

use App\Enums\ConversationStatus;

public function handle(AtomicIAService $service): void
{
    if ($this->conversation->status !== ConversationStatus::Active) {
        return;
    }

    $lastMessage = $this->conversation->latestMessage;

    $response = $service->reply($this->conversation, $lastMessage->body);

    CreateAssistantMessageAction::execute($this->conversation, $response);
}
```

### Por qué es necesario

```
Timeline:
  t0: Usuario envía mensaje → ProcessConversationJob despachado a la cola
  t1: Moderador banea la conversación (status → banned)
  t2: ProcessConversationJob se ejecuta → SIN guard, llama al LLM con contexto baneado
```

Con el guard, en `t2` el job hace `return` sin consumir recursos.

### Mismo guard en `MessageObserver::created()`

Opcionalmente, también en el observer para no despachar el job si la conversación ya no está activa:

```php
// app/Observers/MessageObserver.php — created()

public function created(Message $message): void
{
    if ($message->role !== MessageRole::User) {
        return;
    }

    if ($message->conversation->status !== ConversationStatus::Active) {
        return;
    }

    ProcessConversationJob::dispatch($message->conversation);
}
```

> El guard en el job es obligatorio (la conversación puede cambiar entre despacho y ejecución). El guard en el observer es opcional (defensa en profundidad).

---

## 3. Consistencia de enums en `SendMessageAction`

### Problema

`SendMessageAction` usa el string `'sent'` en vez del enum `MessageStatus::Sent`:

```php
// Actual (inconsistente)
$message = $conversation->messages()->create([
    'status' => 'sent',  // ← string hardcodeado
]);
```

Esto funciona porque el valor coincide, pero:
- Es inconsistente con `CreateAssistantMessageAction` que usa `MessageStatus::Pending`
- Si el valor del enum cambia, este string no se actualiza
- No aparece en búsquedas de uso del enum

### Solución

```php
// app/Actions/SendMessageAction.php

use App\Enums\MessageStatus;

public static function execute(Conversation $conversation, array $data = []): Message
{
    $message = $conversation->messages()->create([
        'role' => MessageRole::User,
        'type' => 'text',
        'body' => data_get($data, 'body'),
        'status' => MessageStatus::Sent,
    ]);

    $conversation->update(['last_message_at' => now()]);

    return $message;
}
```

---

## Orden de implementación

1. Registrar `RateLimiter::for('atomic-ia', ...)` en `AppServiceProvider::boot()`
2. Aplicar middleware `throttle:atomic-ia` a `store` y `newConversation`
3. Agregar guard en `ProcessConversationJob::handle()`
4. Agregar guard en `MessageObserver::created()` (opcional, defensa en profundidad)
5. Cambiar `'sent'` por `MessageStatus::Sent` en `SendMessageAction`
6. Correr pint

---

## Pruebas de validación

| Escenario | Esperado |
|-----------|----------|
| Usuario envía 11 mensajes en 1 minuto | El 11vo retorna HTTP 429 (Too Many Requests) |
| Usuario crea 6 conversaciones en 1 minuto | La 6ta retorna HTTP 429 |
| Job se ejecuta con conversación baneada | Early return, no llama al LLM |
| Mensaje en conversación baneada (observer) | No despacha `ProcessConversationJob` |
| `SendMessageAction` crea mensaje | `status` es instancia de `MessageStatus::Sent` |
