<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Actions;

use Core\BoundedContext\HabitOccurrences\Application\Translators\ScheduleSnapshotToRecurrenceSpec;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrence;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\Services\DateRange;
use Core\BoundedContext\HabitOccurrences\Domain\Services\RecurrenceResolver;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use DateTimeImmutable;

final readonly class ExtendOccurrencesForHabit
{
    public function __construct(
        private HabitOccurrenceRepository $occurrenceRepository,
        private HabitScheduleReader $scheduleReader,
        private RecurrenceResolver $resolver,
        private ScheduleSnapshotToRecurrenceSpec $translator,
    ) {}

    public function __invoke(HabitId $habitId, ?DateTimeImmutable $today = null): int
    {
        $todayDate = OccurrenceDate::fromString(($today ?? new DateTimeImmutable('today'))->format('Y-m-d'));
        $endDate = OccurrenceDate::fromString(
            $todayDate->date()->modify('+12 months')->format('Y-m-d'),
        );

        $lastDate = $this->occurrenceRepository->lastDateForHabit($habitId);
        $from = $lastDate !== null
            ? OccurrenceDate::fromString($lastDate->date()->modify('+1 day')->format('Y-m-d'))
            : $todayDate;

        if ($from->isAfter($endDate)) {
            return 0;
        }

        $window = new DateRange($from, $endDate);
        $snapshots = $this->scheduleReader->findActiveByHabitIds([$habitId->value()]);
        $occurrences = [];

        foreach ($snapshots as $snap) {
            if (! $snap->isActive) {
                continue;
            }
            $spec = $this->translator->translate($snap);
            foreach ($this->resolver->resolve($spec, $window) as $date) {
                $occurrences[] = HabitOccurrence::schedule(
                    habitId: $habitId,
                    date: $date,
                    timeWindow: $spec->timeWindow,
                    scheduleId: HabitScheduleId::from($snap->habitScheduleId),
                );
            }
        }

        return $this->occurrenceRepository->saveMany($occurrences);
    }
}
