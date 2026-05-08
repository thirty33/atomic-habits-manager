<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Actions;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\DTOs\CreateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\Responses\HabitScheduleResponse;
use Core\BoundedContext\HabitSchedules\Domain\HabitSchedule;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\ChainCue;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DateRange;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DaysOfWeek;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\IntervalDays;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\RecurrenceType;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\TimeRange;

final readonly class CreateHabitSchedule
{
    public function __construct(private HabitScheduleRepository $repository) {}

    public function __invoke(CreateHabitScheduleData $data): HabitScheduleResponse
    {
        $startsFrom = $data->startsFrom ?? date('Y-m-d');

        $schedule = HabitSchedule::create(
            habitId: HabitId::from($data->habitId),
            timeRange: TimeRange::from($data->startTime, $data->endTime),
            recurrenceType: RecurrenceType::from($data->recurrenceType),
            dateRange: DateRange::from($startsFrom, $data->endsAt),
            daysOfWeek: $data->daysOfWeek !== null ? DaysOfWeek::from($data->daysOfWeek) : null,
            intervalDays: $data->intervalDays !== null ? IntervalDays::from($data->intervalDays) : null,
            specificDate: $data->specificDate,
            chainCue: $data->chainCue !== null ? ChainCue::from($data->chainCue) : null,
            previousScheduleId: $data->previousScheduleId !== null
                ? HabitScheduleId::from($data->previousScheduleId)
                : null,
        );

        $this->repository->save($schedule);

        return HabitScheduleResponse::fromHabitSchedule($schedule);
    }
}
