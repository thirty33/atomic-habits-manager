<?php

namespace App\Services\Occurrences;

use App\Actions\Occurrences\CreateOccurrencesForHabitAction;
use App\Actions\Occurrences\DeleteFutureOccurrencesAction;
use App\Actions\Occurrences\MarkHabitRebuiltAction;
use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Repositories\HabitRepository;
use App\Repositories\OccurrenceRepository;
use App\Services\Occurrences\Contracts\OccurrenceServiceInterface;
use Carbon\CarbonImmutable;

class OccurrenceService implements OccurrenceServiceInterface
{
    public function __construct(
        private HabitRepository $habitRepository,
        private OccurrenceRepository $occurrenceRepository,
    ) {}

    public function rebuildForHabit(int $habitId): int
    {
        $habit = $this->habitRepository->findWithActiveSchedules($habitId);

        if (! $habit) {
            return 0;
        }

        // Delete future occurrences
        $futureIds = $this->occurrenceRepository->getFutureOccurrenceIds($habitId);
        DeleteFutureOccurrencesAction::execute(['occurrence_ids' => $futureIds->toArray()]);

        // Generate new ones from today to +12 months
        $from = today()->toImmutable();
        $to = today()->addMonths(12)->toImmutable();

        $occurrences = $this->buildOccurrencesFromSchedules($habit, $from, $to);
        $created = CreateOccurrencesForHabitAction::execute(['occurrences' => $occurrences]);

        MarkHabitRebuiltAction::execute(['habit_id' => $habitId]);

        return $created;
    }

    public function extendForHabit(int $habitId): int
    {
        $habit = $this->habitRepository->findWithActiveSchedules($habitId);

        if (! $habit) {
            return 0;
        }

        $lastDate = $this->occurrenceRepository->getLastOccurrenceDate($habitId);
        $from = $lastDate ? $lastDate->addDay() : today()->toImmutable();
        $to = today()->addMonths(12)->toImmutable();

        if ($from->greaterThan($to)) {
            return 0;
        }

        $occurrences = $this->buildOccurrencesFromSchedules($habit, $from, $to);

        return CreateOccurrencesForHabitAction::execute(['occurrences' => $occurrences]);
    }

    public function cleanupForDeletedHabit(int $habitId): int
    {
        $futureIds = $this->occurrenceRepository->getFutureOccurrenceIds($habitId);

        return DeleteFutureOccurrencesAction::execute(['occurrence_ids' => $futureIds->toArray()]);
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    public function resolveDates(HabitSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rawStartsFrom = $schedule->getAttributes()['starts_from'] ?? null;
        $rawEndsAt = $schedule->getAttributes()['ends_at'] ?? null;

        $startsFrom = $rawStartsFrom ? CarbonImmutable::parse($rawStartsFrom) : null;
        $endsAt = $rawEndsAt ? CarbonImmutable::parse($rawEndsAt) : null;

        // Effective range
        $effectiveFrom = $startsFrom && $startsFrom->greaterThan($from) ? $startsFrom : $from;
        $effectiveTo = $endsAt && $endsAt->lessThan($to) ? $endsAt : $to;

        if ($effectiveFrom->greaterThan($effectiveTo)) {
            return [];
        }

        $recurrenceType = $schedule->getAttributes()['recurrence_type'] ?? null;

        return match ($recurrenceType) {
            'daily' => $this->resolveDailyDates($effectiveFrom, $effectiveTo),
            'weekly' => $this->resolveWeeklyDates($schedule, $effectiveFrom, $effectiveTo),
            'every_n_days' => $this->resolveEveryNDaysDates($schedule, $startsFrom, $effectiveFrom, $effectiveTo),
            'none' => $this->resolveNoneDates($schedule, $effectiveFrom, $effectiveTo),
            default => [],
        };
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function resolveDailyDates(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $dates = [];
        $current = $from;

        while ($current->lessThanOrEqualTo($to)) {
            $dates[] = $current;
            $current = $current->addDay();
        }

        return $dates;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function resolveWeeklyDates(HabitSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $daysOfWeek = $schedule->days_of_week;

        if (empty($daysOfWeek)) {
            return [];
        }

        $dates = [];
        $current = $from;

        while ($current->lessThanOrEqualTo($to)) {
            if (in_array($current->dayOfWeek, $daysOfWeek)) {
                $dates[] = $current;
            }
            $current = $current->addDay();
        }

        return $dates;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function resolveEveryNDaysDates(HabitSchedule $schedule, ?CarbonImmutable $startsFrom, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $interval = ($schedule->getAttributes()['interval_days'] ?? null) ?: 1;
        $anchor = $startsFrom ?? $from;

        // Find the first date >= $from that aligns with the interval
        if ($anchor->lessThan($from)) {
            $daysDiff = $anchor->diffInDays($from);
            $remainder = $daysDiff % $interval;
            $current = $remainder === 0 ? $from : $from->addDays($interval - $remainder);
        } else {
            $current = $anchor;
        }

        $dates = [];

        while ($current->lessThanOrEqualTo($to)) {
            $dates[] = $current;
            $current = $current->addDays($interval);
        }

        return $dates;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function resolveNoneDates(HabitSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $specificDate = $schedule->getAttributes()['specific_date'] ?? null;

        if (! $specificDate) {
            return [];
        }

        $date = CarbonImmutable::parse($specificDate);

        if ($date->between($from, $to)) {
            return [$date];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildOccurrencesFromSchedules(Habit $habit, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $occurrences = [];
        $now = now();

        foreach ($habit->schedules as $schedule) {
            $dates = $this->resolveDates($schedule, $from, $to);

            foreach ($dates as $date) {
                $occurrences[] = [
                    'habit_id' => $habit->habit_id,
                    'habit_schedule_id' => $schedule->habit_schedule_id,
                    'occurrence_date' => $date->toDateString(),
                    'start_time' => $schedule->getAttributes()['start_time'] ?? null,
                    'end_time' => $schedule->getAttributes()['end_time'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $occurrences;
    }
}
