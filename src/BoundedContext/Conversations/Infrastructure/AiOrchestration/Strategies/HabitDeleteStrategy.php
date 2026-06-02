<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\DeletableResource;
use Core\BoundedContext\Habits\Application\Actions\DeleteHabit;
use Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\Actions\DeleteHabitSchedule;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final class HabitDeleteStrategy implements DeletableResource
{
    public function __construct(
        private readonly HabitRepository $habits,
        private readonly HabitScheduleRepository $schedules,
        private readonly DeleteHabit $deleteHabit,
        private readonly DeleteHabitSchedule $deleteSchedule,
    ) {}

    public function resourceName(): string
    {
        return 'habits';
    }

    public function resourceDescription(): string
    {
        return 'Elimina un hábito completo (con todas sus programaciones) o solo una programación específica.';
    }

    public function delete(int $userId, int $id, array $data = []): string
    {
        if (isset($data['schedule_id'])) {
            return $this->deleteScheduleOnly($userId, $id, (int) $data['schedule_id']);
        }

        return $this->deleteWholeHabit($userId, $id);
    }

    private function deleteWholeHabit(int $userId, int $id): string
    {
        $habitId = HabitId::from($id);
        $habit = $this->habits->findForUser($habitId, UserId::from($userId));

        if ($habit === null) {
            throw HabitNotFound::withId($habitId);
        }

        $name = $habit->name()->value();

        ($this->deleteHabit)($id, $userId);

        return "Hábito '{$name}' (ID {$id}) eliminado correctamente.";
    }

    private function deleteScheduleOnly(int $userId, int $habitId, int $scheduleId): string
    {
        $habit = $this->habits->findForUser(HabitId::from($habitId), UserId::from($userId));

        if ($habit === null) {
            throw HabitNotFound::withId(HabitId::from($habitId));
        }

        $schedule = $this->schedules->find(HabitScheduleId::from($scheduleId));

        if ($schedule === null || $schedule->habitId()->value() !== $habitId) {
            return 'Error: la programación no pertenece a este hábito.';
        }

        ($this->deleteSchedule)($scheduleId);

        return "Programación ID {$scheduleId} eliminada correctamente.";
    }
}
