<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\EventHandlers;

use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasDeleted;

final readonly class RebuildOccurrencesWhenScheduleWasDeleted
{
    public function __construct(private RebuildOccurrencesForHabit $useCase) {}

    public function __invoke(HabitScheduleWasDeleted $event): void
    {
        ($this->useCase)($event->habitId);
    }
}
