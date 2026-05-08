<?php

namespace App\Observers;

use App\Jobs\CleanupDeletedHabitOccurrencesJob;
use App\Jobs\SyncHabitOccurrencesJob;
use App\Models\Habit;

class HabitObserver
{
    public function updated(Habit $habit): void
    {
        // Avoid infinite loop when the flag itself is changed
        if ($habit->wasChanged('needs_occurrence_rebuild')) {
            return;
        }

        $habit->updateQuietly(['needs_occurrence_rebuild' => true]);
        SyncHabitOccurrencesJob::dispatch($habit->habit_id);
    }

    public function deleted(Habit $habit): void
    {
        CleanupDeletedHabitOccurrencesJob::dispatch($habit->habit_id);
    }

    public function restored(Habit $habit): void
    {
        $habit->updateQuietly(['needs_occurrence_rebuild' => true]);
        SyncHabitOccurrencesJob::dispatch($habit->habit_id);
    }
}
