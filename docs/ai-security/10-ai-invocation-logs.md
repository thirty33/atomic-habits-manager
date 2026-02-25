# 10 — Plan: AI Invocation Logs

## Objetivo

Registrar en base de datos cada invocación a cualquier agente de IA (`AtomicIAAgent`,
`ModeratorAgent`, futuros), almacenando el prompt enviado, la respuesta recibida,
las tool calls ejecutadas y el uso de tokens — todo como strings planos para
minimizar el espacio.

---

## Mecanismo de captura

`laravel/ai` dispara el evento `Laravel\Ai\Events\AgentPrompted` después de completar
**toda** la interacción (incluyendo pasos de tool calls). El evento expone:

| Propiedad | Contenido |
|-----------|-----------|
| `$event->prompt->agent` | Instancia del agente |
| `$event->prompt->prompt` | Prompt enviado (post-middleware) |
| `$event->response->text` | Texto final de la respuesta |
| `$event->response->toolResults` | Colección de `ToolResult` |
| `$event->response->usage` | Tokens usados (`promptTokens`, `completionTokens`) |

Un único listener sobre `AgentPrompted` cubre **todos** los agentes actuales y futuros.

Para obtener el `user_id` desde el listener, los agentes implementarán el contrato
`App\Ai\Contracts\HasUserId`. El listener comprueba `instanceof` y solo rellena
`user_id` si el agente lo implementa (nullable para agentes futuros sin usuario).

---

## Formato de tool_calls (columna `TEXT`)

En lugar de JSON, cada tool call se serializa como una línea compacta:

```
delete_resource(resource="habit", id=3) → "Hábito eliminado correctamente."
delete_resource(resource="habit", id=5) → "Hábito eliminado correctamente."
list_resource(resource="habits") → "Tienes 3 hábitos: Meditar, Ejercicio..."
```

- Una línea por tool call ejecutada
- Argumentos separados por coma dentro del paréntesis
- Resultado truncado a 120 caracteres para no desperdiciar espacio

Si no hubo tool calls, la columna queda `NULL`.

---

## Esquema de base de datos: `ai_invocation_logs`

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | `bigint UNSIGNED AUTO_INCREMENT` | PK |
| `user_id` | `bigint UNSIGNED NULL` | FK → `users.user_id` ON DELETE SET NULL |
| `agent` | `varchar(100)` | Nombre corto de clase (`AtomicIAAgent`) |
| `prompt` | `text` | Prompt enviado al modelo |
| `response` | `text NULL` | Texto de respuesta; NULL si vacía |
| `tool_calls` | `text NULL` | Líneas compactas; NULL si no hubo calls |
| `prompt_tokens` | `smallint UNSIGNED NULL` | Tokens del prompt |
| `completion_tokens` | `smallint UNSIGNED NULL` | Tokens de la respuesta |
| `created_at` | `timestamp DEFAULT CURRENT_TIMESTAMP` | Solo creación, sin `updated_at` |

`user_id` es nullable para agentes futuros que pudieran no tener usuario.
`ON DELETE SET NULL` preserva el log histórico aunque se elimine el usuario.

---

## Archivos a crear / modificar

### 0. Contrato `HasUserId`
`app/Ai/Contracts/HasUserId.php`

```php
interface HasUserId
{
    public function userId(): int;
}
```

Ambos agentes lo implementan:

- **`AtomicIAAgent`** — devuelve `$this->conversation->user_id`
- **`ModeratorAgent`** — devuelve `$this->message->conversation->user_id`

### 1. Migration
`database/migrations/XXXX_create_ai_invocation_logs_table.php`

```php
Schema::create('ai_invocation_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
    $table->string('agent', 100);
    $table->text('prompt');
    $table->text('response')->nullable();
    $table->text('tool_calls')->nullable();
    $table->unsignedSmallInteger('prompt_tokens')->nullable();
    $table->unsignedSmallInteger('completion_tokens')->nullable();
    $table->timestamp('created_at')->useCurrent();
});
```

### 2. Model
`app/Models/AiInvocationLog.php`

- `$timestamps = false` (solo `created_at`, gestionado por la DB)
- Sin fillable/guarded (uso interno, sin input del usuario)
- Cast `created_at` → `datetime`

### 3. Listener
`app/Listeners/LogAiInvocationListener.php`

```php
public function handle(AgentPrompted $event): void
{
    $agent = $event->prompt->agent;

    AiInvocationLog::create([
        'user_id'           => $agent instanceof HasUserId ? $agent->userId() : null,
        'agent'             => class_basename($agent),
        'prompt'            => $event->prompt->prompt,
        'response'          => $event->response->text ?: null,
        'tool_calls'        => $this->formatToolCalls($event->response),
        'prompt_tokens'     => $event->response->usage->promptTokens ?? null,
        'completion_tokens' => $event->response->usage->completionTokens ?? null,
    ]);
}
```

El método privado `formatToolCalls()` usa `$response->toolResults` (colección de
`ToolResult`) para generar las líneas compactas.

### 4. Registro del listener
`app/Providers/AppServiceProvider.php` — añadir en `boot()`:

```php
Event::listen(AgentPrompted::class, LogAiInvocationListener::class);
```

---

## Tests: `tests/Feature/Ai/AiInvocationLogTest.php`

| Test | Qué verifica |
|------|-------------|
| `it_logs_agent_invocation_without_tool_calls` | Fakeando `AtomicIAAgent`, se crea registro con `user_id`, `agent`, `prompt`, `response` correctos y `tool_calls` NULL |
| `it_logs_agent_invocation_with_tool_calls` | Fakeando `AtomicIAAgent` con tool call simulada, `tool_calls` contiene la línea compacta |
| `it_logs_moderator_agent_invocation` | Fakeando `ModeratorAgent`, `agent = "ModeratorAgent"` y `user_id` correcto |
| `it_stores_null_response_when_empty` | Si el modelo devuelve texto vacío, `response` se guarda como NULL |
| `it_stores_null_user_id_for_agentless_agents` | Agente sin `HasUserId` → `user_id` NULL |

Los tests usan `AtomicIAAgent::fake()` para evitar llamadas reales a la API.

---

## Lo que NO hace este plan

- No crea una UI para ver los logs (eso es otro plan)
- No agrega paginación ni limpieza automática de registros viejos
- No indexa `agent` (volumen bajo, no justificado aún)