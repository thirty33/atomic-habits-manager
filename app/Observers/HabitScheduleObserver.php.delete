<?php

namespace App\Observers;

use App\Jobs\SyncHabitOccurrencesJob;
use App\Models\HabitSchedule;

class HabitScheduleObserver
{
    public function created(HabitSchedule $schedule): void
    {
        $this->flagAndSync($schedule);
    }

    public function updated(HabitSchedule $schedule): void
    {
        $this->flagAndSync($schedule);
    }

    public function deleted(HabitSchedule $schedule): void
    {
        $this->flagAndSync($schedule);
    }

    private function flagAndSync(HabitSchedule $schedule): void
    {
        $schedule->habit?->updateQuietly(['needs_occurrence_rebuild' => true]);
        SyncHabitOccurrencesJob::dispatch($schedule->habit_id);
    }
}
