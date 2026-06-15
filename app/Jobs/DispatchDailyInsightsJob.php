<?php

namespace App\Jobs;

use Core\BoundedContext\Habits\Domain\HabitRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Daily fan-out: dispatches one GenerateUserInsightJob per user that owns at
 * least one active habit, so each LLM call is queued and isolated. Scheduled in
 * routes/console.php.
 */
class DispatchDailyInsightsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function handle(HabitRepository $habits): void
    {
        foreach ($habits->userIdsWithActiveHabits() as $userId) {
            GenerateUserInsightJob::dispatch($userId);
        }
    }
}
