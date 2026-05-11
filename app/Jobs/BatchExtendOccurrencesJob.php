<?php

namespace App\Jobs;

use Core\BoundedContext\HabitOccurrences\Application\Actions\ExtendOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
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

    public function handle(ExtendOccurrencesForHabit $extend): void
    {
        foreach ($this->habitIds as $id) {
            $extend(HabitId::from($id));
        }
    }
}
