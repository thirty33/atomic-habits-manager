<?php

namespace App\Jobs;

use App\Services\Occurrences\Contracts\OccurrenceServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncHabitOccurrencesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public int $habitId) {}

    public function handle(OccurrenceServiceInterface $service): void
    {
        $service->rebuildForHabit($this->habitId);
    }
}
