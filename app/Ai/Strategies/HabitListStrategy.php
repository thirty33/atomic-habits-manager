<?php

namespace App\Ai\Strategies;

use App\Ai\Contracts\ListableResource;
use App\Models\Habit;
use App\Repositories\HabitRepository;

class HabitListStrategy implements ListableResource
{
    public function __construct(private HabitRepository $repository) {}

    public function resourceName(): string
    {
        return 'habits';
    }

    public function resourceDescription(): string
    {
        return 'Los hábitos atómicos del usuario con su nombre, naturaleza (construir/romper), importancia y estado.';
    }

    public function list(int $userId, ?int $parentId = null): string
    {
        $habits = $this->repository->getAllForUser($userId);

        if ($habits->isEmpty()) {
            return 'El usuario no tiene hábitos registrados.';
        }

        return $habits->map(fn (Habit $h) => implode(' | ', [
            "ID: {$h->habit_id}",
            "Nombre: {$h->name}",
            "Naturaleza: {$h->habit_nature->label()}",
            "Importancia: {$h->desire_type->label()}",
            'Activo: '.($h->is_active ? 'Sí' : 'No'),
            "Programaciones: {$h->schedules->count()}",
        ]))->implode("\n");
    }
}
