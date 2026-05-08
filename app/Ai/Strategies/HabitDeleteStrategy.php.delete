<?php

namespace App\Ai\Strategies;

use App\Actions\Habits\DeleteHabitAction;
use App\Ai\Contracts\DeletableResource;
use App\Models\Habit;
use App\Models\HabitSchedule;

class HabitDeleteStrategy implements DeletableResource
{
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
            return $this->deleteSchedule($userId, $id, (int) $data['schedule_id']);
        }

        return $this->deleteHabit($userId, $id);
    }

    private function deleteHabit(int $userId, int $id): string
    {
        $habit = Habit::where('user_id', $userId)->findOrFail($id);
        $name = $habit->name;

        DeleteHabitAction::execute($id);

        return "Hábito '{$name}' (ID {$id}) eliminado correctamente.";
    }

    private function deleteSchedule(int $userId, int $habitId, int $scheduleId): string
    {
        Habit::where('user_id', $userId)->findOrFail($habitId);

        $schedule = HabitSchedule::findOrFail($scheduleId);

        if ($schedule->habit_id !== $habitId) {
            return 'Error: la programación no pertenece a este hábito.';
        }

        $schedule->delete();

        return "Programación ID {$scheduleId} eliminada correctamente.";
    }
}
