<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application;

use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;

/**
 * Read-side port for HabitSchedule projections. Returns ReadModels
 * (snapshots) instead of aggregates — read paths skip aggregate
 * hydration on purpose.
 *
 * CQRS counterpart of HabitScheduleRepository (Domain, write-side).
 * Both interfaces are typically adapted by a single Eloquent class in
 * Infrastructure to keep the implementation DRY.
 */
interface HabitScheduleReader
{
    /**
     * @param  list<int>  $habitIds
     * @return array<int, HabitScheduleSnapshot>
     */
    public function findActiveByHabitIds(array $habitIds): array;

    /**
     * Returns ALL ACTIVE schedules of the given habits, grouped by
     * habit_id in lists. Unlike findActiveByHabitIds (which collapses to
     * one snapshot per habit for the backoffice list), this preserves
     * every active schedule — required to materialize occurrences for a
     * habit that has more than one active schedule.
     *
     * @param  list<int>  $habitIds
     * @return array<int, list<HabitScheduleSnapshot>>
     */
    public function findAllActiveByHabitIds(array $habitIds): array;

    /**
     * Returns ALL schedules (active and inactive) of the given habits,
     * grouped by habit_id in lists. Preserves the parity with the legacy
     * eager-load `with('schedules')` of App\Repositories\HabitRepository.
     *
     * Unlike findActiveByHabitIds, this does NOT filter by is_active and
     * returns a LIST per habit (a habit may have multiple schedules).
     *
     * @param  list<int>  $habitIds
     * @return array<int, list<HabitScheduleSnapshot>>
     */
    public function findByHabitIds(array $habitIds): array;
}
