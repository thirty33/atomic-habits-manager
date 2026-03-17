<?php

namespace App\Repositories;

use App\Models\DailyReport;
use App\Models\Habit;
use App\Models\HabitOccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DailyReportRepository
{
    public function queryForUser(int $userId): Builder
    {
        return DailyReport::query()
            ->where('user_id', $userId)
            ->withCount([
                'entries',
                'entries as entries_reported_count' => fn ($q) => $q->where('status', '!=', 'pending'),
            ])
            ->latest('report_date');
    }

    public function findForUserAndDate(int $userId, string $date): ?DailyReport
    {
        return DailyReport::query()
            ->where('user_id', $userId)
            ->where('report_date', $date)
            ->with(['entries.habit', 'entries.occurrence'])
            ->first();
    }

    public function findWithEntries(int $reportId): ?DailyReport
    {
        return DailyReport::query()
            ->with(['entries' => fn ($q) => $q->orderBy('start_time'), 'entries.habit', 'entries.occurrence'])
            ->find($reportId);
    }

    /**
     * @return Collection<int, HabitOccurrence>
     */
    public function getOccurrencesForDate(int $userId, string $date): Collection
    {
        return HabitOccurrence::query()
            ->whereHas('habit', fn ($q) => $q->where('user_id', $userId))
            ->where('occurrence_date', $date)
            ->with('habit:habit_id,name,color,habit_nature,desire_type,is_active')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return Collection<int, Habit>
     */
    public function getActiveHabitsForUser(int $userId): Collection
    {
        return Habit::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['habit_id', 'name', 'color', 'habit_nature', 'desire_type']);
    }
}
