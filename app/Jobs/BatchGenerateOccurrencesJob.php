<?php

namespace App\Jobs;

use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BatchGenerateOccurrencesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    /**
     * @param  array<int, int>  $habitIds
     */
    public function __construct(public array $habitIds) {}

    public function handle(RebuildOccurrencesForHabit $rebuild): void
    {
        foreach ($this->habitIds as $id) {
            $rebuild(HabitId::from($id));
        }
    }
}
