<?php

namespace App\Repositories;

use App\Models\Habit;
use Carbon\CarbonImmutable;
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

    /**
     * @return Collection<int, int>
     */
    public function getHabitIdsNeedingRebuild(): Collection
    {
        return Habit::query()
            ->where('is_active', true)
            ->where('needs_occurrence_rebuild', true)
            ->pluck('habit_id');
    }

    /**
     * @return Collection<int, int>
     */
    /**
     * @return Collection<int, int>
     */
    public function getHabitIdsNeedingExtension(CarbonImmutable $threshold): Collection
    {
        return Habit::query()
            ->where('is_active', true)
            ->where('needs_occurrence_rebuild', false)
            ->whereHas('occurrences')
            ->whereDoesntHave('occurrences', function ($q) use ($threshold) {
                $q->where('occurrence_date', '>=', $threshold->toDateString());
            })
            ->pluck('habit_id');
    }

    public function findWithActiveSchedules(int $habitId): ?Habit
    {
        return Habit::query()
            ->with(['schedules' => fn ($q) => $q->where('is_active', true)])
            ->find($habitId);
    }
}
