# Refactor / test log

Bitácora de incidencias encontradas durante el refactor DDD del módulo Atomic IA y la pasada de regresión browser.

Cada entrada documenta el síntoma, la causa raíz, el fix aplicado, su evaluación frente a las reglas DDD del proyecto, y cualquier deuda pendiente.

## [1] `DispatchDomainEventJob` legacy referenciaba `OutboxEntryDto::eventType` (no existe)

**Síntoma observado:** ejecutar el job lanza `Property OutboxEntryDto::$eventType does not exist`. Detectado al leer el archivo `app/Jobs/DispatchDomainEventJob.php` antes de refactorizarlo.

**Stack/log relevante:** WIP no committeado de la infraestructura outbox; la fila `id, event_id, event_name, payload, occurred_on, ...` de la tabla `domain_event_outbox` se serializa en `OutboxEntryDto` con `eventName`, no `eventType`. El job hacía `$serializer->deserialize($entry->eventType, ...)`.

**Causa raíz:** typo introducido cuando se renombró `event_type` → `event_name` en la migración. El DTO se actualizó; el job legacy quedó atrás.

**Fix aplicado:** sustituido por el trait `App\Jobs\Concerns\DispatchesDomainEvent` (ver flow 12 commit), que usa `$entry->eventName`. El legacy `DispatchDomainEventJob` se reescribió sobre el trait, marcado `@deprecated`, y queda en disco para absorber jobs en vuelo de antes del deploy.

**¿Respeta DDD?** Sí. El fix vive en Infrastructure (`app/Jobs/`), no toca Domain ni Application. La idempotencia por `completedAt !== null` se mantiene. Cero locks, cero `App\…` en el camino de deserialización.

**Pendiente:** ninguno — los buckets nuevos (`Default`, `Heavy`, `Critical`) sustituyen al legacy en cualquier flujo nuevo.

---

## [2] La tabla `conversations` NO tiene `deleted_at` — discrepancia con el doc 09

**Síntoma observado:** al escribir el test del flujo 09 (`DeleteConversation`), `assertSoftDeleted('conversations', ...)` falla con `Column not found: 1054 Unknown column 'deleted_at' in 'where clause'`.

**Stack/log relevante:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'deleted_at' in 'where clause'
(select * from `conversations` where (`conversation_id` = 4) and `deleted_at` is not null)
```

**Causa raíz:** `database/migrations/2026_02_09_224951_create_conversations_table.php` define la tabla SIN `softDeletes()`. El base model `App\Models\Base\Conversation` no usa el trait `SoftDeletes`. El doc 09 (§"Notas") afirma "El borrado sigue siendo soft-delete (la tabla `conversations` tiene `deleted_at`)" — afirmación incorrecta sobre el estado real de la BD.

**Fix aplicado:** mantengo el comportamiento real (hard delete con cascade en `messages` por la FK `cascadeOnDelete()`), exactamente igual que el legacy `DeleteConversationAction`. Tests usan `assertDatabaseMissing` en vez de `assertSoftDeleted`. Decisión justificada por la regla del prompt: "ambigüedades en docs → opción conservadora" (no introducir cambios de schema sin autorización).

**¿Respeta DDD?** Sí. El método de dominio `Conversation::delete()` solo registra `ConversationWasDeleted`; el adapter Eloquent traduce eso a `delete()` (hard delete según schema actual). Si en el futuro se añade `softDeletes()`, solo cambia el adapter — el dominio no se entera.

**Pendiente:** decidir con el usuario si el doc 09 debe corregirse para reflejar el estado actual, o si el schema debe migrarse para añadir soft delete. Mientras tanto, el comportamiento es consistente entre legacy y DDD.
