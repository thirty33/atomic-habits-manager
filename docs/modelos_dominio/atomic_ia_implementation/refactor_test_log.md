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

## [3] ~~Concesión temporal en `LaravelAiResponseProvider`: `loginUsingId` para Tools legacy~~ — **CERRADA EN FLOW 10**

**Síntoma observado:** las Tools del SDK (`app/Ai/Tools/{List,Create,Update,...}ResourceTool.php`) leían `auth()->id()` dentro de `handle()`. El adapter `LaravelAiResponseProvider` cubría esto con `loginUsingId` durante el intervalo flow 04 → flow 10.

**Estado:** **resuelta.** Flow 10 (commit "DDD refactor flow 10: Tools with userId by constructor — closes BUG-2"):

- 9 Tools migradas a `Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\*`. Las 7 Tools de recurso reciben `int $userId` por constructor; cero `auth()->id()` en sus `handle()`.
- `LaravelAiResponseProvider::buildTools($userId)` cablea `$conversation->userId()->value()` a cada Tool. La dependencia `Illuminate\Contracts\Auth\Factory` y la llamada `loginUsingId` desaparecieron del adapter.
- `ConversationServiceProvider` ya no inyecta `AuthFactory` al provider.
- `app/Ai/{Tools,Strategies}/*.php` renombrados a `.php.delete`. `app/Ai/` queda con sólo Agents legacy en `.php.delete` y la carpeta vacía de Strategies/Tools.

**¿Respeta DDD?** Sí, ahora plenamente. Application/Domain de Conversations no toca Auth. Infrastructure (`LaravelAiResponseProvider`) sólo lee `$conversation->userId()->value()` del agregado y lo propaga.

---

## [4] `MessageObserver` legacy mantiene rama `updated` durante el intervalo flow 04 → flow 07/08

**Síntoma observado:** la rama `created` del observer (que despachaba `ProcessConversationJob` para mensajes de usuario) ya está reemplazada por el listener `ScheduleAiResponse`. Pero las ramas `updated → Approved → MessageSent::dispatch` y `updated → Banned → CreateFallbackMessageAction::execute` siguen vivas hasta que aterricen los flujos 07 (banear → fallback) y 08 (broadcast aprobado).

**Causa raíz:** los flujos están ordenados — flow 04 antes que 07/08. Si removiéramos el observer entero ahora, los broadcasts y los fallbacks se romperían entre commits.

**Fix aplicado:** la rama `created` se elimina en este commit; las ramas `updated` se preservan con un docblock que las marca como temporales hasta flujos 07/08. Eliminada la lógica obsoleta de "is user role" + "dispatch ProcessConversationJob".

**¿Respeta DDD?** Sí, como medida transitoria. El observer es Infrastructure y no toca Domain/Application. Cuando flujos 07/08 introduzcan los listeners `BroadcastApprovedMessage` y `PostFallbackOnBan`, se elimina el observer entero (renombrado a `.php.delete`).

**Pendiente:** flujo 07 elimina la rama Banned; flujo 08 elimina la rama Approved; observer renombrado a `.php.delete` cuando ambas se vayan.

---

## [5] Flow 06 quedó sin sus bindings de producción (silently green tests)

**Síntoma observado:** durante flow 07 inspeccioné `app/Providers/ConversationServiceProvider.php` y descubrí que mis ediciones de flow 06 (binding `AiModerationProvider`, registro de `AssistantMessageWasApproved/Banned` + `ConversationWasBanned` en el `DomainEventClassRegistry`) NO aterrizaron al disco a pesar de que la herramienta reportó éxito.

**Stack/log relevante:** los tests de flow 06 pasaban (34/34) porque cada test usa `$this->app->instance(AiModerationProvider::class, $this->aiModerator)` para inyectar el doble en el contenedor. Cuando un binding NO existe en el provider pero se sobrescribe en el test con `instance(...)`, Laravel acepta la inyección sin quejarse — el test verde es engañoso.

**Causa raíz:** las ediciones a `ConversationServiceProvider` se aplicaron en memoria pero el archivo de disco mantuvo la versión de flow 04. Hipótesis: race condition entre Edit y un proceso externo (probablemente Pint del flow 04 commit) que dejó la versión final del archivo. La verificación que faltó: `git diff` antes del commit.

**Fix aplicado:** flow 07 reescribe el provider completo con TODOS los bindings que deberían haber estado: `AiResponseProvider` (flow 04), `AiModerationProvider` (flow 06), `ConversationBroadcaster` (flow 07), todos los registros de eventos en `DomainEventClassRegistry`, todas las suscripciones (`ScheduleAiResponse`, `BroadcastConversationStatus`, `PostFallbackOnBan`).

**¿Respeta DDD?** Sí. El provider está en Infrastructure (`app/Providers/`), no toca dominio. Las suscripciones declarativas están donde corresponde. Lo que falló fue el proceso de aplicación del cambio, no la arquitectura.

**Pendiente:** lección — verificar `git diff <archivo>` ANTES de cada commit para confirmar que el delta esperado coincide con el delta real. Pint puede deshacer cambios si interactúa mal con ediciones in-flight.

---

## [6] Eventos de dominio de `Habits` instanciados con argumento nombrado obsoleto `occurredOn:`

**Síntoma observado:** al ejercitar `HabitWriteStrategiesTest` post-flow 10, todos los tests que ejecutan `CreateHabit` / `UpdateHabit` Use Cases lanzan `Error: Unknown named parameter $occurredOn` desde `HabitWasCreated::__construct` (y los demás eventos hermanos). El test no llegaba ni a tocar la lógica de la Strategy.

**Causa raíz:** el constructor base `Core\Shared\Domain\Events\DomainEvent` declara su parámetro temporal como `protected DateTimeImmutable $occurredAt`. Los 4 eventos del BC `Habits` (`HabitWasCreated`, `HabitWasUpdated`, `HabitWasSoftDeleted`, `HabitWasRestored`) declaraban un parámetro local `?DateTimeImmutable $occurredOn = null` y reenviaban con `parent::__construct(occurredOn: ...)`, llamando a un argumento que el padre no expone — `Unknown named parameter`.

**Fix aplicado:** unificar el nombre en los 4 eventos del BC: parámetro local renombrado a `$occurredAt`, y `parent::__construct(occurredAt: ..., eventId: ...)` ya empareja con la firma del padre. Sustitución mecánica con `sed` sobre `src/BoundedContext/Habits/Domain/Events/*.php`.

**¿Respeta DDD?** Sí. Los eventos son Domain; el bug era discrepancia interna de naming, no de arquitectura. La fachada pública (`occurredOn(): DateTimeImmutable` en el padre) se preserva — sólo el parámetro del constructor cambia.

**Pendiente:** ninguno. Los eventos `HabitSchedules\Domain\Events\*` y `Conversations\Domain\Events\*` ya usaban `occurredAt` desde el inicio.

---

## [7] `RebuildOccurrencesForHabit` / `ExtendOccurrencesForHabit` importaban `HabitScheduleId` del namespace incorrecto

**Síntoma observado:** `Class "Core\BoundedContext\HabitSchedules\Domain\ValueObjects\HabitScheduleId" not found` al regenerar ocurrencias durante los tests del flow 10.

**Causa raíz:** el VO real vive en `…\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId` (la convención del proyecto separa `Concretes/` de `Primitives/`). Los dos Use Cases de `HabitOccurrences/Application/Actions/` lo importaban sin el segmento `Concretes\\`.

**Fix aplicado:** corregir el `use` en `RebuildOccurrencesForHabit.php` y `ExtendOccurrencesForHabit.php`. Cero cambios funcionales.

**¿Respeta DDD?** Sí. Lo único movido es un import alineándose a la convención del repositorio.

**Pendiente:** ninguno.

---

## [8] Observers Eloquent legacy (`HabitObserver`, `HabitScheduleObserver`) referenciaban Jobs ya retirados

**Síntoma observado:** cualquier test que crea/actualiza un `Habit` o `HabitSchedule` falla con `Class "App\Jobs\SyncHabitOccurrencesJob" not found` al disparar el observer.

**Causa raíz:** los observers Eloquent eran el cableado legacy entre cambios en los modelos y la regeneración de occurrences. El nuevo cableado pasa por Domain Events emitidos desde los agregados (`HabitWasUpdated`, `HabitScheduleWasCreated/Updated/Deleted`) y listeners en `HabitOccurrences/Application/EventHandlers/Rebuild*`. Los observers se quedaron en disco apuntando a `App\Jobs\SyncHabitOccurrencesJob` y `App\Jobs\CleanupDeletedHabitOccurrencesJob`, ambos ya como `.php.delete`.

**Fix aplicado:**

- `HabitObserver.php`, `HabitScheduleObserver.php` → renombrados a `.php.delete`.
- `Habit::observe(...)` y `HabitSchedule::observe(...)` retirados de `AppServiceProvider::boot`.
- `App\Services\Occurrences\OccurrenceService` (que también importaba Actions legacy) renombrado a `.php.delete` junto con su `OccurrenceServiceInterface`. El binding en `AppServiceProvider::register` se eliminó. Cero consumidores en `src/` ni en `app/Ai/`.
- 3 tests legacy retirados a `.php.delete` con autorización del usuario:
  - `tests/Feature/Backoffice/HabitObserverTest.php` (probaba los observers retirados)
  - `tests/Feature/Backoffice/SyncHabitOccurrencesJobTest.php` (probaba job ya retirado)
  - `tests/Feature/Backoffice/OccurrenceServiceTest.php` (probaba service y Actions ya retirados)

  La cobertura del flujo de regeneración vive ahora en `tests/.../HabitOccurrences/EventHandlers/*` y los listeners cableados en `HabitServiceProvider`.

**¿Respeta DDD?** Sí. La retirada de los observers consolida la regla "Eloquent es Infraestructura; el cableado de comportamiento vive en Domain Events emitidos por agregados". El BC `HabitOccurrences` se mantiene desacoplado del `App\Models\HabitSchedule` excepto por el adapter Eloquent del repositorio.

**Pendiente:** ninguno para BUG-2. Anotado para fase 2: borrar definitivamente los `.php.delete` después de un sprint sin regresiones (mismo criterio que el `app/Repositories/HabitRepository.php` del doc 99).

---

## [9] Strategies usaban `value` (acceso a propiedad) en lugar de `value()` (método del VO)

**Síntoma observado:** `Cannot access protected property Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature::$value` al ejecutar `HabitUpdateStrategy::update`.

**Causa raíz:** en el BC `Habits`, los VOs `HabitNature` y `DesireType` extienden `StringEnum`, que declara `protected string $value` y expone `public function value(): string`. La Strategy nueva (migrada de `app/Ai/Strategies/HabitUpdateStrategy.php` legacy) llamaba `$habit->habitNature()->value` — sintaxis válida sólo para backed enums nativos de PHP, no para VOs.

**Fix aplicado:** sustituir `$habit->habitNature()->value` → `$habit->habitNature()->value()` y lo mismo para `desireType()`. Sólo dos líneas en `HabitUpdateStrategy.php`.

**¿Respeta DDD?** Sí — el VO sigue encapsulando su valor; el cliente respeta la API pública.

**Pendiente:** ninguno.

---

## Pre-existentes fuera del alcance de BUG-2 (deuda técnica conocida)

Detectados al correr la suite completa post-flow 10. **No se corrigen aquí** porque tocan dominios ajenos al cierre de BUG-2; se documentan para limpieza posterior.

| Test | Síntoma | Causa | Pista para fix |
|---|---|---|---|
| `tests/Feature/Backoffice/GenerateHabitOccurrencesCommandTest` | `Column 'habit_occurrences.deleted_at' not found` | `EloquentHabitRepository::pendingExtensionIds` (`src/BoundedContext/Habits/Infrastructure/Persistence/Eloquent/EloquentHabitRepository.php:142,146`) hace `whereNull('habit_occurrences.deleted_at')` pero la tabla `habit_occurrences` no tiene esa columna ni el modelo usa `SoftDeletes` | borrar los `whereNull('…deleted_at')` del query — la lógica nunca filtró soft-deletes en esta tabla |
| `tests/Feature/Backoffice/CalendarOccurrencesTest > occurrences ordered by …` | falla relacionada al mismo path de regeneración | derivado del bug anterior | mismo fix |
| `tests/Feature/Auth/EmailVerificationTest` | `UrlGenerationException` | rutas de email verification probablemente incompletas en el setup nuevo | revisar `routes/auth.php` / Breeze |
| `tests/Feature/Auth/AuthenticationTest > users can logout` | response inesperada en logout | misma área | idem |
| `tests/Feature/ProfileTest > user can delete their account` / `email verification status …` | `$user->fresh()` no es null tras delete | el modelo `User` usa `SoftDeletes`; el test asume hard delete. Probablemente discrepancia entre Breeze por defecto (hard delete) y la decisión de usar soft delete en `User` | actualizar el test a `assertSoftDeleted(...)` (o quitar SoftDeletes del modelo si nunca se va a usar) |
