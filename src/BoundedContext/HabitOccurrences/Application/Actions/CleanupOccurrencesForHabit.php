<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Actions;

use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use DateTimeImmutable;

final readonly class CleanupOccurrencesForHabit
{
    public function __construct(
        private HabitOccurrenceRepository $occurrenceRepository,
    ) {}

    public function __invoke(HabitId $habitId, ?DateTimeImmutable $today = null): int
    {
        $todayDate = OccurrenceDate::fromString(
            ($today ?? new DateTimeImmutable('today'))->format('Y-m-d'),
        );

        $futureIds = $this->occurrenceRepository->futureIdsForHabit($habitId, $todayDate);

        if ($futureIds === []) {
            return 0;
        }

        return $this->occurrenceRepository->deleteByIds($futureIds);
    }
}
