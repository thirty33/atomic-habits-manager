<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\EventHandlers;

use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\Events\HabitWasRestored;

final readonly class RebuildOccurrencesWhenHabitWasRestored
{
    public function __construct(private RebuildOccurrencesForHabit $useCase) {}

    public function __invoke(HabitWasRestored $event): void
    {
        ($this->useCase)($event->habitId);
    }
}
