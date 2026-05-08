<?php

namespace App\Services\Occurrences\Contracts;

use App\Models\HabitSchedule;
use Carbon\CarbonImmutable;

interface OccurrenceServiceInterface
{
    public function rebuildForHabit(int $habitId): int;

    public function extendForHabit(int $habitId): int;

    public function cleanupForDeletedHabit(int $habitId): int;

    /**
     * @return array<int, CarbonImmutable>
     */
    public function resolveDates(HabitSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to): array;
}
