# 08 — Tools de Escritura con Patrón Strategy (Create, Update, Delete)

## Objetivo

Extender el agente conversacional con tools de escritura para que la IA pueda crear, actualizar y
eliminar registros de `habits` y `habit_schedules`. Dado que ambas tablas están fuertemente
relacionadas, cada strategy opera sobre **ambas tablas a la vez** — la strategy de hábitos es
la unidad cohesiva que encapsula toda la lógica de su dominio.

> Nota de diseño: este acoplamiento estrategia↔tablas es una decisión de dominio, no una regla
> del patrón. En otros contextos una strategy podría cubrir una sola tabla.

---

## Tablas en alcance

| Tabla             | Gestionada por              |
|-------------------|-----------------------------|
| `habits`          | `HabitCreateStrategy`, `HabitUpdateStrategy`, `HabitDeleteStrategy` |
| `habit_schedules` | Las mismas tres strategies  |

---

## Patrón aplicado: Strategy (extensión del existente)

```
CreateResourceTool          UpdateResourceTool          DeleteResourceTool
      │ usa                       │ usa                       │ usa
CreatableResource           UpdatableResource           DeletableResource
      │ implementa                │ implementa                │ implementa
HabitCreateStrategy         HabitUpdateStrategy         HabitDeleteStrategy
  (habits + schedules)        (habits + schedules)        (habits + schedules)
```

---

## Nuevos contratos (interfaces)

### `app/Ai/Contracts/CreatableResource.php`

```php
interface CreatableResource
{
    public function resourceName(): string;
    public function resourceDescription(): string;

    /** Fields the AI must collect before calling create(). */
    public function requiredFields(): array;

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): string;
}
```

### `app/Ai/Contracts/UpdatableResource.php`

```php
interface UpdatableResource
{
    public function resourceName(): string;
    public function resourceDescription(): string;
    public function updatableFields(): array;
    public function update(int $userId, int $id, array $data): string;
}
```

### `app/Ai/Contracts/DeletableResource.php`

```php
interface DeletableResource
{
    public function resourceName(): string;
    public function resourceDescription(): string;
    public function delete(int $userId, int $id): string;
}
```

---

## Nuevas tools genéricas

### `app/Ai/Tools/CreateResourceTool.php`

- Schema: `resource` (enum dinámico) + `data` (object con los campos del recurso).
- Delega a `$strategy->create(auth()->id(), $data)`.
- Retorna el mensaje de confirmación de la strategy.

### `app/Ai/Tools/UpdateResourceTool.php`

- Schema: `resource` (enum) + `id` (integer, requerido) + `data` (object con campos a actualizar).
- Delega a `$strategy->update(auth()->id(), $id, $data)`.

### `app/Ai/Tools/DeleteResourceTool.php`

- Schema: `resource` (enum) + `id` (integer, requerido).
- Delega a `$strategy->delete(auth()->id(), $id)`.

---

## Estrategias concretas para hábitos + programaciones

### `HabitCreateStrategy` — crea hábito con programación opcional

- **Hábito** (requerido): `name`, `habit_nature` (build|break), `desire_type` (need|want|neutral).
- **Hábito** (opcional): `description`, `implementation_intention`, `location`, `cue`, `reframe`, `is_active`.
- **Programación** (opcional): si el usuario provee `schedule`, delega a `CreateHabitScheduleAction`.
  - `schedule.recurrence_type` (none|daily|weekly|every_n_days)
  - `schedule.start_time`, `schedule.end_time`
  - `schedule.days_of_week` — requerido si `recurrence_type = weekly`
  - `schedule.interval_days` — requerido si `recurrence_type = every_n_days`
  - `schedule.specific_date` — requerido si `recurrence_type = none`
- Delega a `CreateHabitAction::execute($data)`.
- Retorna: `"Hábito '{$name}' creado con ID {$id}."` + confirmación de schedule si se creó.

### `HabitUpdateStrategy` — actualiza hábito y/o sus programaciones

- Recibe `id` del hábito. Valida ownership con `Habit::where('user_id', $userId)->findOrFail($id)`.
- Si `data` contiene campos del hábito → delega a `UpdateHabitAction::execute($id, $data)`.
- Si `data` contiene `schedule` con `schedule_id` → delega a `UpdateHabitScheduleAction::execute($scheduleId, $data['schedule'])`.
- Si `data` contiene `schedule` sin `schedule_id` → crea nueva programación con `CreateHabitScheduleAction`.
- Retorna resumen de qué se actualizó.

### `HabitDeleteStrategy` — elimina hábito o una de sus programaciones

- Recibe `id` (del hábito) y opcionalmente `schedule_id`.
- Si solo viene `id` → elimina el hábito completo con `DeleteHabitAction::execute($id)` (cascade borra sus schedules).
- Si viene `schedule_id` → valida que el schedule pertenece a un hábito del usuario y lo elimina.
- Retorna confirmación del recurso eliminado.

---

## Registro en `AtomicIAAgent`

```php
public function tools(): iterable
{
    return [
        new GreetTool,
        new ListResourceTool(
            new HabitListStrategy(app(HabitRepository::class)),
        ),
        new CreateResourceTool(
            new HabitCreateStrategy,
        ),
        new UpdateResourceTool(
            new HabitUpdateStrategy,
        ),
        new DeleteResourceTool(
            new HabitDeleteStrategy,
        ),
    ];
}
```

Actualizar `instructions()` — bloque `## Capacidades actuales`:

```
- Consultar los hábitos del usuario y sus programaciones
- Crear nuevos hábitos con o sin programación inicial
- Actualizar un hábito y/o sus programaciones
- Eliminar un hábito completo, o solo una programación específica
```

---

## Seguridad (obligatorio en cada strategy)

| Riesgo | Mitigación |
|--------|-----------|
| Modificar hábito de otro usuario | `Habit::where('user_id', $userId)->findOrFail($id)` antes de operar |
| Modificar schedule de hábito ajeno | Verificar `$schedule->habit->user_id === $userId` |
| Eliminar sin confirmación | La IA debe pedir confirmación explícita al usuario antes de llamar delete |
| Enums inválidos | Validar `HabitNature`, `DesireType`, `RecurrenceType` antes de pasar a la Action |

---

## Orden de implementación

1. Crear las 3 interfaces: `CreatableResource`, `UpdatableResource`, `DeletableResource`
2. Crear `CreateResourceTool`, `UpdateResourceTool`, `DeleteResourceTool`
3. Crear `HabitCreateStrategy`, `HabitUpdateStrategy`, `HabitDeleteStrategy`
4. Registrar en `AtomicIAAgent::tools()` y actualizar `instructions()`
5. Correr pint
6. Tests

---

## Pruebas de validación

| Escenario | Esperado |
|-----------|----------|
| "Crea un hábito llamado Leer" | IA solicita campos faltantes, luego llama `CreateResourceTool` |
| "Crea el hábito Meditar a las 7am todos los días" | IA crea hábito + schedule en una llamada |
| "Actualiza el hábito ID 7, ponlo inactivo" | `UpdateResourceTool` con `id: 7`, `is_active: false` |
| "Cambia la programación ID 4 del hábito 7 a las 9am" | `UpdateResourceTool` con `schedule_id: 4` |
| "Elimina el hábito ID 7" | IA pide confirmación, luego `DeleteResourceTool` con `id: 7` |
| "Elimina la programación ID 4" | `DeleteResourceTool` con `id: 7`, `schedule_id: 4` |
| Hábito de otro usuario | Strategy filtra con `user_id` — retorna error |