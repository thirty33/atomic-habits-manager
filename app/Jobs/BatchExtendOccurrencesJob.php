<?php

namespace App\Jobs;

use App\Services\Occurrences\Contracts\OccurrenceServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BatchExtendOccurrencesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    /**
     * @param  array<int, int>  $habitIds
     */
    public function __construct(public array $habitIds) {}

    public function handle(OccurrenceServiceInterface $service): void
    {
        foreach ($this->habitIds as $habitId) {
            $service->extendForHabit($habitId);
        }
    }
}
