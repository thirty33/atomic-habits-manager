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

---

## [3] Concesión temporal en `LaravelAiResponseProvider`: `loginUsingId` para Tools legacy

**Síntoma observado:** las Tools del SDK (`app/Ai/Tools/{List,Create,Update,...}ResourceTool.php`) leen `auth()->id()` dentro de `handle()` — patrón legacy que el flow 10 va a refactorizar para que reciban `int $userId` por constructor. Mientras tanto el adapter `LaravelAiResponseProvider` necesita garantizar que `auth()->id()` devuelva el id del owner cuando la Tool se invoque, porque el path nuevo (listener → bucket Job → Use Case → adapter) NO pasa por una request HTTP que tenga sesión activa.

**Causa raíz:** staging — fase 1 (BUG-2 closure, doc 99) y fase 2 (flujos individuales) se ejecutan en pasos separados. El doc 04 §"Cambios concretos" pone la refactor de Tools en flujo 10, no en 04. Se mantiene la dependencia legacy a `auth()->id()` durante el intervalo entre flow 04 y flow 10.

**Fix aplicado:** dentro de `LaravelAiResponseProvider::respondTo`, antes de construir el `AtomicIAAgent`, se ejecuta `$this->auth->guard('web')->loginUsingId($conversation->userId()->value())`. Es el equivalente al `auth()->loginUsingId(...)` que hacía el `ProcessConversationJob` legacy. Documentado en el docblock de la clase como "Concession to be cleaned up in flow 10".

**¿Respeta DDD?** Parcialmente. El adapter está en Infrastructure, donde sí está permitido tocar `Illuminate\Contracts\Auth\Factory`. Pero la dirección que da el usuario en el prompt — "Tools del SDK reciben int $userId por constructor; cero auth()->id() y cero auth()->loginUsingId()" — exige eliminarlo. La regla se cumple cuando aterrice flow 10. **Marca esta entrada como deuda técnica con plazo definido.**

**Pendiente:** flow 10 debe (1) refactorizar las 8 Tools para tomar `int $userId` por constructor, (2) eliminar `auth()->id()` de sus `handle()`, (3) eliminar el `loginUsingId` del adapter, (4) eliminar la dependencia `AuthFactory` del constructor del adapter.

---

## [4] `MessageObserver` legacy mantiene rama `updated` durante el intervalo flow 04 → flow 07/08

**Síntoma observado:** la rama `created` del observer (que despachaba `ProcessConversationJob` para mensajes de usuario) ya está reemplazada por el listener `ScheduleAiResponse`. Pero las ramas `updated → Approved → MessageSent::dispatch` y `updated → Banned → CreateFallbackMessageAction::execute` siguen vivas hasta que aterricen los flujos 07 (banear → fallback) y 08 (broadcast aprobado).

**Causa raíz:** los flujos están ordenados — flow 04 antes que 07/08. Si removiéramos el observer entero ahora, los broadcasts y los fallbacks se romperían entre commits.

**Fix aplicado:** la rama `created` se elimina en este commit; las ramas `updated` se preservan con un docblock que las marca como temporales hasta flujos 07/08. Eliminada la lógica obsoleta de "is user role" + "dispatch ProcessConversationJob".

**¿Respeta DDD?** Sí, como medida transitoria. El observer es Infrastructure y no toca Domain/Application. Cuando flujos 07/08 introduzcan los listeners `BroadcastApprovedMessage` y `PostFallbackOnBan`, se elimina el observer entero (renombrado a `.php.delete`).

**Pendiente:** flujo 07 elimina la rama Banned; flujo 08 elimina la rama Approved; observer renombrado a `.php.delete` cuando ambas se vayan.
