<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain;

use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;

interface HabitScheduleRepository
{
    public function save(HabitSchedule $schedule): void;

    public function find(HabitScheduleId $id): ?HabitSchedule;

    public function delete(HabitSchedule $schedule): void;

    /**
     * @param  list<int>  $habitIds
     * @return array<int, HabitScheduleSnapshot>
     */
    public function findActiveByHabitIds(array $habitIds): array;

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
