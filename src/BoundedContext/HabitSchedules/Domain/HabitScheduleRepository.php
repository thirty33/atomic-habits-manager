<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain;

use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;

/**
 * Write-side port for the HabitSchedule aggregate. CQRS: read-side
 * queries that return projections live in HabitScheduleReader
 * (Application).
 *
 * Purity rules: zero imports from Application, Illuminate, or App\.
 */
interface HabitScheduleRepository
{
    public function save(HabitSchedule $schedule): void;

    public function find(HabitScheduleId $id): ?HabitSchedule;

    public function delete(HabitSchedule $schedule): void;
}
