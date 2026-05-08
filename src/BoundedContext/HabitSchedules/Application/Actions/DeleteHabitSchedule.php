<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Actions;

use Core\BoundedContext\HabitSchedules\Domain\Exceptions\HabitScheduleNotFound;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;

final readonly class DeleteHabitSchedule
{
    public function __construct(private HabitScheduleRepository $repository) {}

    public function __invoke(int $habitScheduleId): void
    {
        $id = HabitScheduleId::from($habitScheduleId);

        $schedule = $this->repository->find($id);

        if ($schedule === null) {
            throw HabitScheduleNotFound::withId($id);
        }

        $schedule->markForDeletion();

        $this->repository->delete($schedule);
    }
}
