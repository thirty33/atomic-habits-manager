<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\EventHandlers;

use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasUpdated;

final readonly class RebuildOccurrencesWhenScheduleWasUpdated
{
    public function __construct(private RebuildOccurrencesForHabit $useCase) {}

    public function __invoke(HabitScheduleWasUpdated $event): void
    {
        ($this->useCase)($event->habitId);
    }
}
