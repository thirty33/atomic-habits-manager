<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\EventHandlers;

use Core\BoundedContext\HabitOccurrences\Application\Actions\CleanupOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\Events\HabitWasSoftDeleted;

final readonly class CleanupOccurrencesWhenHabitWasSoftDeleted
{
    public function __construct(private CleanupOccurrencesForHabit $useCase) {}

    public function __invoke(HabitWasSoftDeleted $event): void
    {
        ($this->useCase)($event->habitId);
    }
}
