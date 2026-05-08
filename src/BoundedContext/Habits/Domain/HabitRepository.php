<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain;

use Core\BoundedContext\Habits\Domain\Criteria\HabitsCriteria;
use Core\BoundedContext\Habits\Domain\Criteria\HabitsPage;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Puerto de persistencia para el agregado Habit.
 *
 * Esta interfaz vive en Domain — el dominio describe qué necesita, no cómo
 * se guarda. La implementación (adapter) vive en Infrastructure y traduce
 * estos contratos a Eloquent / SQL / etc.
 *
 * Reglas de pureza:
 * - Cero imports de `Illuminate\…`, `App\…`, ni de cualquier capa externa.
 * - Solo tipos del propio dominio (Habit, HabitId, UserId, HabitsCriteria,
 *   HabitsPage, Habits) y primitivas PHP.
 */
interface HabitRepository
{
    /**
     * Persiste un Habit (insert si es nuevo, update si ya tiene id).
     *
     * Si el habit es nuevo, este método debe asignarle un HabitId al objeto
     * vía `$habit->assignId(...)` después de obtener el id generado por la
     * base de datos.
     */
    public function save(Habit $habit): void;

    /**
     * Devuelve el Habit con ese id, o null si no existe (incluyendo soft-deleted).
     */
    public function find(HabitId $id): ?Habit;

    /**
     * Devuelve el Habit si pertenece al UserId indicado; null en cualquier
     * otro caso (no existe, soft-deleted, o pertenece a otro usuario).
     */
    public function findForUser(HabitId $id, UserId $userId): ?Habit;

    /**
     * Borra (soft-delete) el Habit. Asume que el habit ya tiene id asignado.
     */
    public function delete(Habit $habit): void;

    /**
     * Devuelve TODOS los habits de un usuario, sin paginar. Pensado para
     * exports / CLI / casos donde el volumen es manejable.
     */
    public function findAllForUser(UserId $userId): Habits;

    /**
     * Aplica un criterio (filtros + sort + paginación) y devuelve una página.
     *
     * Por qué Criteria en vez de Builder: la interfaz no debe conocer
     * Eloquent. Criteria es un VO de dominio que el adapter sabe traducir.
     */
    public function matching(HabitsCriteria $criteria): HabitsPage;

    /**
     * Devuelve los habits activos (is_active=true, no soft-deleted) del usuario.
     * Pensado para el calendario y otros lectores que solo necesitan los
     * vigentes.
     */
    public function findActiveForUser(UserId $userId): Habits;

    /**
     * IDs de habits con `needs_occurrence_rebuild=true`. Consumido por el
     * scheduler/job que regenera occurrences tras cambios de programación.
     *
     * @return list<int>
     */
    public function pendingRebuildIds(): array;

    /**
     * IDs de habits activos cuya última occurrence cae antes o en el threshold
     * indicado (YYYY-MM-DD). Consumido por el job de extensión rolling.
     *
     * @return list<int>
     */
    public function pendingExtensionIds(string $thresholdYmd): array;

    /**
     * Marca un habit como rebuild-completado: needs_occurrence_rebuild=false
     * sin tocar updated_at. Usado al final del job de regeneración.
     */
    public function markRebuilt(HabitId $id): void;
}
