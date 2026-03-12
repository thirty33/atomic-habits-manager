<?php

namespace App\Jobs;

use App\Services\Occurrences\Contracts\OccurrenceServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupDeletedHabitOccurrencesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $habitId) {}

    public function handle(OccurrenceServiceInterface $service): void
    {
        $service->cleanupForDeletedHabit($this->habitId);
    }
}
