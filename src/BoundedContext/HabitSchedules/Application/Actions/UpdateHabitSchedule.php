<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Actions;

use Core\BoundedContext\HabitSchedules\Application\DTOs\UpdateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\Responses\HabitScheduleResponse;
use Core\BoundedContext\HabitSchedules\Domain\Exceptions\HabitScheduleNotFound;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\ChainCue;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DateRange;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DaysOfWeek;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\IntervalDays;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\RecurrenceType;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\TimeRange;

final readonly class UpdateHabitSchedule
{
    public function __construct(private HabitScheduleRepository $repository) {}

    public function __invoke(UpdateHabitScheduleData $data): HabitScheduleResponse
    {
        $habitScheduleId = HabitScheduleId::from($data->habitScheduleId);

        $schedule = $this->repository->find($habitScheduleId);

        if ($schedule === null) {
            throw HabitScheduleNotFound::withId($habitScheduleId);
        }

        $schedule->update(
            timeRange: TimeRange::from($data->startTime, $data->endTime),
            recurrenceType: RecurrenceType::from($data->recurrenceType),
            dateRange: DateRange::from(
                $data->startsFrom ?? $schedule->dateRange()->startsFrom,
                $data->endsAt,
            ),
            daysOfWeek: $data->daysOfWeek !== null ? DaysOfWeek::from($data->daysOfWeek) : null,
            intervalDays: $data->intervalDays !== null ? IntervalDays::from($data->intervalDays) : null,
            specificDate: $data->specificDate,
            chainCue: $data->chainCue !== null ? ChainCue::from($data->chainCue) : null,
        );

        $this->repository->save($schedule);

        return HabitScheduleResponse::fromHabitSchedule($schedule);
    }
}
