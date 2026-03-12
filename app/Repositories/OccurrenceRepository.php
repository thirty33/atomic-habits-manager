<?php

namespace App\Repositories;

use App\Models\HabitOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OccurrenceRepository
{
    /**
     * @return Collection<int, int>
     */
    public function getFutureOccurrenceIds(int $habitId): Collection
    {
        return HabitOccurrence::query()
            ->where('habit_id', $habitId)
            ->where('occurrence_date', '>=', now()->toDateString())
            ->pluck('habit_occurrence_id');
    }

    public function getLastOccurrenceDate(int $habitId): ?CarbonImmutable
    {
        $date = HabitOccurrence::query()
            ->where('habit_id', $habitId)
            ->max('occurrence_date');

        return $date ? CarbonImmutable::parse($date) : null;
    }

    /**
     * @return Collection<int, HabitOccurrence>
     */
    public function getForHabitInRange(int $habitId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return HabitOccurrence::query()
            ->where('habit_id', $habitId)
            ->whereBetween('occurrence_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('occurrence_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return Collection<int, HabitOccurrence>
     */
    public function getForUserInRange(int $userId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return HabitOccurrence::query()
            ->whereHas('habit', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('occurrence_date', [$from->toDateString(), $to->toDateString()])
            ->with('habit:habit_id,name,color,habit_nature,desire_type,is_active')
            ->orderBy('occurrence_date')
            ->orderBy('start_time')
            ->get();
    }
}
