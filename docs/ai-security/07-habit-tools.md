# 07 — Tool de Lectura con Patrón Strategy

## Objetivo

Crear una tool de lectura genérica para el agente conversacional que sea **agnóstica a la tabla que consulta**. La tool sabe que debe listar recursos, pero delega a una estrategia concreta qué tabla leer y cómo formatear los resultados. Agregar o quitar tablas es solo configuración.

---

## Patrón aplicado: Strategy

```
                    ┌──────────────────────┐
                    │   ListResourceTool   │  ← Tool (contexto)
                    │                      │
                    │  - resources: Map     │  ← estrategias registradas
                    │  + handle(request)    │  ← resuelve la estrategia y delega
                    └──────────┬───────────┘
                               │ usa
                    ┌──────────▼───────────┐
                    │  ListableResource    │  ← Interfaz (contrato)
                    │                      │
                    │  + resourceName()    │
                    │  + description()     │
                    │  + list(userId, id?) │
                    └──────────┬───────────┘
                               │ implementa
                    ┌──────────▼───────────┐
                    │  HabitListStrategy   │  ← Estrategia concreta
                    └──────────────────────┘
```

**Principio**: La tool es el **contexto** que consume estrategias intercambiables. Cada estrategia encapsula la query y el formato de una tabla concreta. Al agente solo se le pasan las estrategias que tiene permiso de leer.

---

## Componentes a crear

| Archivo | Descripción |
|---------|-------------|
| `app/Ai/Contracts/ListableResource.php` | Interfaz Strategy — contrato para recursos listables |
| `app/Ai/Strategies/HabitListStrategy.php` | Estrategia concreta — lista hábitos del usuario |
| `app/Ai/Tools/ListResourceTool.php` | Tool genérica — contexto del Strategy |
| `app/Repositories/HabitRepository.php` | Queries de lectura para hábitos |

## Componentes a modificar

| Archivo | Cambio |
|---------|--------|
| `app/Ai/Agents/AtomicIAAgent.php` | Registrar `ListResourceTool` con `HabitListStrategy`, actualizar `instructions()` |

---

## 1. Interfaz `ListableResource` (contrato)

Define qué debe saber hacer cualquier recurso que se pueda listar.

```php
// app/Ai/Contracts/ListableResource.php

namespace App\Ai\Contracts;

interface ListableResource
{
    /**
     * Identificador del recurso para el schema de la tool.
     * Ej: 'habits'
     */
    public function resourceName(): string;

    /**
     * Descripción para que la IA entienda qué contiene este recurso.
     */
    public function resourceDescription(): string;

    /**
     * Ejecuta la consulta y devuelve texto formateado para la IA.
     *
     * @param int $userId ID del usuario autenticado
     * @param int|null $parentId ID del recurso padre (para recursos hijos)
     */
    public function list(int $userId, ?int $parentId = null): string;
}
```

---

## 2. HabitRepository (queries)

La estrategia no consulta la BD directamente — delega al repository.

```php
// app/Repositories/HabitRepository.php

namespace App\Repositories;

use App\Models\Habit;
use Illuminate\Support\Collection;

class HabitRepository
{
    public function getAllForUser(int $userId): Collection
    {
        return Habit::query()
            ->forUser($userId)
            ->with('schedules')
            ->latest()
            ->get();
    }
}
```

---

## 3. HabitListStrategy (estrategia concreta)

```php
// app/Ai/Strategies/HabitListStrategy.php

namespace App\Ai\Strategies;

use App\Ai\Contracts\ListableResource;
use App\Models\Habit;
use App\Repositories\HabitRepository;

class HabitListStrategy implements ListableResource
{
    public function __construct(private HabitRepository $repository) {}

    public function resourceName(): string
    {
        return 'habits';
    }

    public function resourceDescription(): string
    {
        return 'Los hábitos atómicos del usuario con su nombre, naturaleza (construir/romper), importancia y estado.';
    }

    public function list(int $userId, ?int $parentId = null): string
    {
        $habits = $this->repository->getAllForUser($userId);

        if ($habits->isEmpty()) {
            return 'El usuario no tiene hábitos registrados.';
        }

        return $habits->map(fn (Habit $h) => implode(' | ', [
            "ID: {$h->habit_id}",
            "Nombre: {$h->name}",
            "Naturaleza: {$h->habit_nature->label()}",
            "Importancia: {$h->desire_type->label()}",
            "Activo: " . ($h->is_active ? 'Sí' : 'No'),
            "Programaciones: {$h->schedules->count()}",
        ]))->implode("\n");
    }
}
```

---

## 4. ListResourceTool (contexto del Strategy)

La tool recibe las estrategias como constructor args. Su schema `resource` es un enum dinámico generado a partir de las estrategias registradas.

```php
// app/Ai/Tools/ListResourceTool.php

namespace App\Ai\Tools;

use App\Ai\Contracts\ListableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListResourceTool implements Tool
{
    /** @var array<string, ListableResource> */
    private array $resources = [];

    public function __construct(ListableResource ...$resources)
    {
        foreach ($resources as $resource) {
            $this->resources[$resource->resourceName()] = $resource;
        }
    }

    public function description(): Stringable|string
    {
        $descriptions = array_map(
            fn (ListableResource $r) => "- {$r->resourceName()}: {$r->resourceDescription()}",
            $this->resources
        );

        return "Lista recursos del usuario.\n\nRecursos disponibles:\n" . implode("\n", $descriptions);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->enum(array_keys($this->resources))
                ->description('Tipo de recurso a listar.')
                ->required(),
            'parent_id' => $schema->integer()
                ->description('ID del recurso padre. Requerido para recursos hijos.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $resourceName = $request['resource'];

        if (! isset($this->resources[$resourceName])) {
            return "Recurso '{$resourceName}' no disponible.";
        }

        return $this->resources[$resourceName]->list(
            auth()->id(),
            isset($request['parent_id']) ? (int) $request['parent_id'] : null
        );
    }
}
```

---

## 5. Registro en AtomicIAAgent

### `tools()` — inyectar la estrategia de hábitos

```php
// app/Ai/Agents/AtomicIAAgent.php

public function tools(): iterable
{
    $habitRepository = app(HabitRepository::class);

    return [
        new GreetTool,
        new ListResourceTool(
            new HabitListStrategy($habitRepository),
        ),
    ];
}
```

### `instructions()` — actualizar capacidades

Añadir al bloque `## Capacidades actuales`:

```
## Capacidades actuales
- Saludar al usuario de forma personalizada
- Consultar los hábitos del usuario: ver la lista con su estado, naturaleza e importancia
- Responder preguntas sobre hábitos atómicos basándose en los datos del usuario
- Informar amablemente que estás en desarrollo para funciones que aún no tienes
```

---

## Cómo escalar (referencia futura)

### Agregar un nuevo recurso listable

1. Crear estrategia que implemente `ListableResource`
2. Añadirla al constructor de `ListResourceTool` en `AtomicIAAgent::tools()`
3. No se toca la tool, ni la interfaz, ni las estrategias existentes

### Permisos de lectura por contexto

La tool no cambia. Solo cambia qué estrategias recibe:

```php
public function tools(): iterable
{
    $strategies = [new HabitListStrategy($repo)];

    if ($condition) {
        $strategies[] = new OtherListStrategy($repo);
    }

    return [
        new GreetTool,
        new ListResourceTool(...$strategies),
    ];
}
```

---

## Orden de implementación

1. Crear `app/Ai/Contracts/ListableResource.php` (interfaz)
2. Crear `app/Repositories/HabitRepository.php` (queries)
3. Crear `app/Ai/Strategies/HabitListStrategy.php`
4. Crear `app/Ai/Tools/ListResourceTool.php`
5. Actualizar `AtomicIAAgent::tools()` con la estrategia
6. Actualizar `AtomicIAAgent::instructions()` con las nuevas capacidades
7. Correr pint
8. Tests

---

## Pruebas de validación

| Escenario | Esperado |
|-----------|----------|
| "Muéstrame mis hábitos" | IA llama `ListResourceTool` con `resource: habits` |
| Usuario sin hábitos | Retorna "El usuario no tiene hábitos registrados" |
| Hábito de otro usuario | `forUser()` filtra — no aparece en la lista |