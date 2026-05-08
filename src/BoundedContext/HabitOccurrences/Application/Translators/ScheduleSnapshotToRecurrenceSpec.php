<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Translators;

use Core\BoundedContext\HabitOccurrences\Domain\Services\RecurrenceSpec;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use InvalidArgumentException;

final readonly class ScheduleSnapshotToRecurrenceSpec
{
    public function translate(HabitScheduleSnapshot $snap): RecurrenceSpec
    {
        $time = new OccurrenceTime(
            $snap->startTime ?? throw new InvalidArgumentException('Schedule snapshot missing start_time'),
            $snap->endTime ?? throw new InvalidArgumentException('Schedule snapshot missing end_time'),
        );

        $startsFrom = $snap->startsFrom !== null ? OccurrenceDate::fromString($snap->startsFrom) : null;
        $endsAt = $snap->endsAt !== null ? OccurrenceDate::fromString($snap->endsAt) : null;

        return match ($snap->recurrenceType) {
            'daily' => RecurrenceSpec::daily($time, $startsFrom, $endsAt),
            'weekly' => RecurrenceSpec::weekly(
                $time,
                $snap->daysOfWeek ?? throw new InvalidArgumentException('Weekly schedule missing days_of_week'),
                $startsFrom,
                $endsAt,
            ),
            'every_n_days' => RecurrenceSpec::everyNDays(
                $time,
                $snap->intervalDays ?? throw new InvalidArgumentException('every_n_days schedule missing interval_days'),
                $startsFrom ?? throw new InvalidArgumentException('every_n_days schedule missing starts_from'),
                $endsAt,
            ),
            'none' => RecurrenceSpec::oneOff(
                $time,
                OccurrenceDate::fromString(
                    $snap->specificDate ?? throw new InvalidArgumentException('one-off schedule missing specific_date'),
                ),
            ),
            default => throw new InvalidArgumentException("Unknown recurrence_type: {$snap->recurrenceType}"),
        };
    }
}
