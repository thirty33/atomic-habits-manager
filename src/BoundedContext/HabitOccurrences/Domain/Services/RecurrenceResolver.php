<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\Services;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;

final readonly class RecurrenceResolver
{
    /**
     * @return list<OccurrenceDate>
     */
    public function resolve(RecurrenceSpec $spec, DateRange $window): array
    {
        $effectiveFrom = $this->effectiveFrom($spec, $window);
        $effectiveTo = $this->effectiveTo($spec, $window);

        if ($effectiveFrom->isAfter($effectiveTo)) {
            return [];
        }

        return match ($spec->type) {
            RecurrenceSpec::TYPE_DAILY => $this->resolveDaily($effectiveFrom, $effectiveTo),
            RecurrenceSpec::TYPE_WEEKLY => $this->resolveWeekly($spec, $effectiveFrom, $effectiveTo),
            RecurrenceSpec::TYPE_EVERY_N_DAYS => $this->resolveEveryNDays($spec, $effectiveFrom, $effectiveTo),
            RecurrenceSpec::TYPE_NONE => $this->resolveOneOff($spec, $window),
            default => [],
        };
    }

    private function effectiveFrom(RecurrenceSpec $spec, DateRange $window): OccurrenceDate
    {
        if ($spec->startsFrom === null) {
            return $window->from;
        }

        return $spec->startsFrom->isAfter($window->from) ? $spec->startsFrom : $window->from;
    }

    private function effectiveTo(RecurrenceSpec $spec, DateRange $window): OccurrenceDate
    {
        if ($spec->endsAt === null) {
            return $window->to;
        }

        return $spec->endsAt->isBefore($window->to) ? $spec->endsAt : $window->to;
    }

    /**
     * @return list<OccurrenceDate>
     */
    private function resolveDaily(OccurrenceDate $from, OccurrenceDate $to): array
    {
        $dates = [];
        $current = $from->date();
        $end = $to->date();

        while ($current <= $end) {
            $dates[] = OccurrenceDate::fromString($current->format('Y-m-d'));
            $current = $current->modify('+1 day');
        }

        return $dates;
    }

    /**
     * @return list<OccurrenceDate>
     */
    private function resolveWeekly(RecurrenceSpec $spec, OccurrenceDate $from, OccurrenceDate $to): array
    {
        $allowed = $spec->daysOfWeek ?? [];
        $dates = [];
        $current = $from->date();
        $end = $to->date();

        while ($current <= $end) {
            $dow = (int) $current->format('w');
            if (in_array($dow, $allowed, true)) {
                $dates[] = OccurrenceDate::fromString($current->format('Y-m-d'));
            }
            $current = $current->modify('+1 day');
        }

        return $dates;
    }

    /**
     * @return list<OccurrenceDate>
     */
    private function resolveEveryNDays(RecurrenceSpec $spec, OccurrenceDate $from, OccurrenceDate $to): array
    {
        $interval = $spec->intervalDays ?? 1;
        $anchor = $spec->startsFrom ?? $from;

        $current = $anchor->date();
        while ($current < $from->date()) {
            $current = $current->modify("+{$interval} day");
        }

        $end = $to->date();
        $dates = [];
        while ($current <= $end) {
            $dates[] = OccurrenceDate::fromString($current->format('Y-m-d'));
            $current = $current->modify("+{$interval} day");
        }

        return $dates;
    }

    /**
     * @return list<OccurrenceDate>
     */
    private function resolveOneOff(RecurrenceSpec $spec, DateRange $window): array
    {
        $specific = $spec->specificDate;
        if ($specific === null) {
            return [];
        }

        return $window->contains($specific) ? [$specific] : [];
    }
}
