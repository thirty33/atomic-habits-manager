<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Actions;

use Core\BoundedContext\HabitOccurrences\Application\Translators\ScheduleSnapshotToRecurrenceSpec;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrence;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\Services\DateRange;
use Core\BoundedContext\HabitOccurrences\Domain\Services\RecurrenceResolver;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use DateTimeImmutable;

final readonly class RebuildOccurrencesForHabit
{
    public function __construct(
        private HabitOccurrenceRepository $occurrenceRepository,
        private HabitScheduleReader $scheduleReader,
        private HabitRepository $habitRepository,
        private RecurrenceResolver $resolver,
        private ScheduleSnapshotToRecurrenceSpec $translator,
    ) {}

    public function __invoke(HabitId $habitId, ?DateTimeImmutable $today = null): int
    {
        if ($this->habitRepository->find($habitId) === null) {
            return 0;
        }

        $todayDate = $this->todayOf($today);

        $futureIds = $this->occurrenceRepository->futureIdsForHabit($habitId, $todayDate);
        if ($futureIds !== []) {
            $this->occurrenceRepository->deleteByIds($futureIds);
        }

        $snapshots = $this->scheduleReader->findAllActiveByHabitIds([$habitId->value()])[$habitId->value()] ?? [];

        $occurrences = $this->buildOccurrences($habitId, $snapshots, $todayDate);

        $created = $this->occurrenceRepository->saveMany($occurrences);

        $this->habitRepository->markRebuilt($habitId);

        return $created;
    }

    /**
     * @param  list<\Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot>  $snapshots
     * @return list<HabitOccurrence>
     */
    private function buildOccurrences(HabitId $habitId, array $snapshots, OccurrenceDate $today): array
    {
        $window = new DateRange($today, $this->plusMonths($today, 12));
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

        return $occurrences;
    }

    private function todayOf(?DateTimeImmutable $today): OccurrenceDate
    {
        $reference = $today ?? new DateTimeImmutable('today');

        return OccurrenceDate::fromString($reference->format('Y-m-d'));
    }

    private function plusMonths(OccurrenceDate $date, int $months): OccurrenceDate
    {
        $shifted = $date->date()->modify("+{$months} months");

        return OccurrenceDate::fromString($shifted->format('Y-m-d'));
    }
}
