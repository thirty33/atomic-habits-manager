# 09 — Plan de Testing: Write Tools y Strategies

## Objetivo

Probar el comportamiento de las tools y strategies de escritura **sin integración con la IA**.
La IA es solo un cliente que llama estas tools; los tests verifican que las tools funcionan
correctamente cuando se les pasa data directamente, igual que lo haría la IA.

---

## Arquitectura a testear

```
[AI request] → Tool::handle(Request) → Strategy::create/update/delete → DB
```

Los tests sustituyen `[AI request]` por llamadas directas a la estrategia o tool.

---

## Tipos de test

| Tipo | Descripción | Clase de test |
|------|-------------|---------------|
| Feature | Estrategias con DB real | `HabitWriteStrategiesTest` |
| Unit | Tools: schema + delegación a strategy | `CreateResourceToolTest`, `UpdateResourceToolTest`, `DeleteResourceToolTest` |

---

## Feature: `HabitWriteStrategiesTest`

Prueba las estrategias directamente contra la base de datos.
Necesita `RefreshDatabase` + usuario autenticado (las Actions usan `auth()->id()`).

### Setup

```php
private User $user;
private User $otherUser;

protected function setUp(): void
{
    parent::setUp();
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
}
```

---

### HabitCreateStrategy

| # | Escenario | Verifica |
|---|-----------|---------|
| 1 | Crear hábito con campos mínimos (name, habit_nature, desire_type) | Registro en `habits` con `user_id` correcto |
| 2 | Crear hábito con todos los campos opcionales | Todos los campos guardados correctamente |
| 3 | Crear hábito + schedule diario | Registro en `habits` + registro en `habit_schedules` con `recurrence_type = daily` |
| 4 | Crear hábito + schedule semanal con `days_of_week` | `days_of_week` guardado como array JSON |
| 5 | Crear hábito + schedule `every_n_days` con `interval_days` | `interval_days` guardado correctamente |
| 6 | Crear hábito + schedule `none` con `specific_date` | `specific_date` guardado correctamente |
| 7 | `habit_nature` inválido | Lanza excepción (enum inválido) |
| 8 | `schedule_recurrence_type` inválido | Lanza excepción (enum inválido) |
| 9 | Crear sin schedule (no se proporcionan `schedule_*`) | No se crea registro en `habit_schedules` |

---

### HabitUpdateStrategy

| # | Escenario | Verifica |
|---|-----------|---------|
| 10 | Actualizar solo `name` | Campo `name` cambiado, resto intacto |
| 11 | Actualizar `is_active = false` | Campo `is_active` es `false` (booleano, no nullable) |
| 12 | Actualizar `habit_nature` | Campo `color` también se actualiza automáticamente |
| 13 | Actualizar campos del hábito + crear nueva programación (sin `schedule_id`) | Hábito actualizado + nueva fila en `habit_schedules` |
| 14 | Actualizar programación existente (con `schedule_id`) | Fila en `habit_schedules` modificada |
| 15 | Enviar `schedule_id` que pertenece a otro hábito | Retorna mensaje de error, no modifica nada |
| 16 | Hábito de otro usuario | `findOrFail` lanza `ModelNotFoundException` |
| 17 | No se proveen campos | Retorna mensaje "No se proporcionaron campos para actualizar" |

---

### HabitDeleteStrategy

| # | Escenario | Verifica |
|---|-----------|---------|
| 18 | Eliminar hábito completo | Hábito soft-deleted (`deleted_at` no nulo) |
| 19 | Eliminar programación específica (con `schedule_id`) | Schedule eliminado, hábito intacto |
| 20 | `schedule_id` pertenece a otro hábito | Retorna mensaje de error, no elimina nada |
| 21 | Hábito de otro usuario (delete hábito) | `ModelNotFoundException` |
| 22 | Hábito de otro usuario (delete schedule) | `ModelNotFoundException` (en la validación del hábito) |

---

## Unit: Tools

Prueba que las tools construyen el schema y delegan correctamente.
Usa Mocks para la strategy — **sin DB**.

---

### `CreateResourceToolTest`

| # | Escenario | Verifica |
|---|-----------|---------|
| 23 | `description()` incluye `resourceName` y campos requeridos | String contiene nombre del recurso y lista de campos |
| 24 | `schema()` incluye `resource` como primer campo | `resource` está en el array devuelto |
| 25 | `schema()` delega a `strategy->schemaFields()` | Se fusionan los campos de la strategy con `resource` |
| 26 | `handle()` extrae solo los campos de `fieldNames()` e ignora el resto | Solo los campos conocidos van a `strategy->create()` |
| 27 | `handle()` omite campos ausentes (no los envía como null) | `strategy->create()` recibe solo los campos presentes en el request |

---

### `UpdateResourceToolTest`

| # | Escenario | Verifica |
|---|-----------|---------|
| 28 | `schema()` incluye `resource` e `id` como campos base | Ambos presentes en el array |
| 29 | `schema()` delega a `strategy->schemaFields()` | Se fusionan los campos de la strategy |
| 30 | `handle()` pasa `id` como entero a `strategy->update()` | Cast correcto de string → int |
| 31 | `handle()` extrae solo los campos de `fieldNames()` | Solo campos conocidos van a la strategy |

---

### `DeleteResourceToolTest`

| # | Escenario | Verifica |
|---|-----------|---------|
| 32 | `schema()` tiene `resource`, `id` (required) y `schedule_id` (opcional) | Los tres campos presentes |
| 33 | `handle()` sin `schedule_id` → llama `strategy->delete()` con `data = []` | `data` vacío |
| 34 | `handle()` con `schedule_id` → llama `strategy->delete()` con `data = ['schedule_id' => N]` | `schedule_id` en data |
| 35 | `description()` menciona que se debe pedir confirmación al usuario | String contiene "confirmación" |

---

## Orden de implementación

1. `HabitWriteStrategiesTest` (feature, todos los escenarios 1–22)
2. `CreateResourceToolTest` (unit, escenarios 23–27)
3. `UpdateResourceToolTest` (unit, escenarios 28–31)
4. `DeleteResourceToolTest` (unit, escenarios 32–35)

---

## Notas técnicas

### Auth en los tests
Las Actions (`CreateHabitAction`, `DeleteHabitAction`) usan `auth()->id()`.
Todos los tests feature deben llamar `$this->actingAs($this->user)` antes de invocar la strategy.

### Simular el `Request` de la tool en unit tests
`Laravel\Ai\Tools\Request` es array-accessible. En unit tests, usar un array stub
o un mock que implemente `ArrayAccess`:

```php
$request = new ArrayObject(['resource' => 'habits', 'name' => 'Leer', ...]);
// O mock con Mockery
```

### Factories faltantes
No existen factories para `Habit` ni `HabitSchedule`. Los tests crearán los registros
directamente con `Habit::create([...])` y `HabitSchedule::create([...])`.

### Campos opcionales y null
`HabitUpdateStrategy` usa `array_intersect_key` para no pisar campos no enviados.
Los tests deben verificar que campos no incluidos en `$data` no se modifican en DB.
