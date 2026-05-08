<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Actions;

use Core\BoundedContext\HabitSchedules\Application\Responses\HabitScheduleResponse;
use Core\BoundedContext\HabitSchedules\Domain\Exceptions\HabitScheduleNotFound;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;

final readonly class FindHabitSchedule
{
    public function __construct(private HabitScheduleRepository $repository) {}

    public function __invoke(int $habitScheduleId): HabitScheduleResponse
    {
        $id = HabitScheduleId::from($habitScheduleId);

        $schedule = $this->repository->find($id);

        if ($schedule === null) {
            throw HabitScheduleNotFound::withId($id);
        }

        return HabitScheduleResponse::fromHabitSchedule($schedule);
    }
}
