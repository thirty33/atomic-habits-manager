<?php

namespace App\Ai\Strategies;

use App\Ai\Contracts\ListableResource;
use App\Enums\RecurrenceType;
use App\Models\Habit;
use App\Models\HabitSchedule;
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
        return 'Los hábitos atómicos del usuario con su nombre, naturaleza (construir/romper), importancia, estado y programaciones.';
    }

    public function list(int $userId, ?int $parentId = null): string
    {
        $habits = $this->repository->getAllForUser($userId);

        if ($habits->isEmpty()) {
            return 'El usuario no tiene hábitos registrados.';
        }

        return $habits->map(function (Habit $h) {
            $lines = [implode(' | ', array_filter([
                "ID: {$h->habit_id}",
                "Nombre: {$h->name}",
                "Naturaleza: {$h->habit_nature->label()}",
                "Importancia: {$h->desire_type->label()}",
                'Activo: '.($h->is_active ? 'Sí' : 'No'),
                $h->description ? "Descripción: {$h->description}" : null,
                $h->implementation_intention ? "Intención de implementación: {$h->implementation_intention}" : null,
                $h->location ? "Lugar: {$h->location}" : null,
                $h->cue ? "Señal: {$h->cue}" : null,
                $h->reframe ? "Reformulación positiva: {$h->reframe}" : null,
            ]))];

            foreach ($h->schedules as $schedule) {
                $lines[] = '  Programacion ID: '.$schedule->habit_schedule_id.' | '.$this->formatSchedule($schedule);
            }

            return implode("\n", $lines);
        })->implode("\n");
    }

    private function formatSchedule(HabitSchedule $schedule): string
    {
        $recurrence = RecurrenceType::from($schedule->recurrence_type)->label();
        $time = $schedule->start_time
            ? $schedule->start_time.' - '.$schedule->end_time
            : 'Sin hora';

        $extra = match (RecurrenceType::from($schedule->recurrence_type)) {
            RecurrenceType::WEEKLY => 'Días: '.implode(', ', $schedule->days_of_week ?? []),
            RecurrenceType::EVERY_N_DAYS => "Cada {$schedule->interval_days} días",
            RecurrenceType::NONE => $schedule->specific_date ? "Fecha: {$schedule->specific_date}" : '',
            default => '',
        };

        return implode(' | ', array_filter([
            "Recurrencia: {$recurrence}",
            "Hora: {$time}",
            $extra ?: null,
            $schedule->chain_cue ? "Cue: {$schedule->chain_cue}" : null,
            'Activo: '.($schedule->is_active ? 'Sí' : 'No'),
        ]));
    }
}
