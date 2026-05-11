<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\ListableResource;
use Core\BoundedContext\Habits\Domain\Habit;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\RecurrenceType;

final class HabitListStrategy implements ListableResource
{
    public function __construct(
        private readonly HabitRepository $habits,
        private readonly HabitScheduleReader $schedules,
    ) {}

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
        $habits = $this->habits->findAllForUser(UserId::from($userId));

        if ($habits->count() === 0) {
            return 'El usuario no tiene hábitos registrados.';
        }

        $habitIds = array_map(static fn (Habit $h) => $h->habitId()->value(), $habits->items());
        $schedulesByHabit = $this->schedules->findByHabitIds($habitIds);

        return implode("\n", $habits->map(function (Habit $h) use ($schedulesByHabit) {
            $lines = [implode(' | ', array_filter([
                "ID: {$h->habitId()->value()}",
                "Nombre: {$h->name()->value()}",
                "Naturaleza: {$h->habitNature()->label()}",
                "Importancia: {$h->desireType()->label()}",
                'Activo: '.($h->isActive() ? 'Sí' : 'No'),
                $h->description() !== null ? "Descripción: {$h->description()->value()}" : null,
                $h->implementationIntention() !== null ? "Intención de implementación: {$h->implementationIntention()->value()}" : null,
                $h->location() !== null ? "Lugar: {$h->location()->value()}" : null,
                $h->cue() !== null ? "Señal: {$h->cue()->value()}" : null,
                $h->reframe() !== null ? "Reformulación positiva: {$h->reframe()->value()}" : null,
            ]))];

            foreach ($schedulesByHabit[$h->habitId()->value()] ?? [] as $snap) {
                $lines[] = '  Programacion ID: '.$snap->habitScheduleId.' | '.$this->formatSchedule($snap);
            }

            return implode("\n", $lines);
        }));
    }

    private function formatSchedule(HabitScheduleSnapshot $snap): string
    {
        $type = RecurrenceType::from($snap->recurrenceType);
        $rec = match (true) {
            $type->isNone() => 'Solo una vez',
            $type->isDaily() => 'Todos los días',
            $type->isWeekly() => 'Algunos días de la semana',
            $type->isEveryNDays() => 'Cada ciertos días',
        };
        $time = $snap->startTime !== null
            ? $snap->startTime.' - '.($snap->endTime ?? '')
            : 'Sin hora';

        $extra = match (true) {
            $type->isWeekly() => 'Días: '.implode(', ', $snap->daysOfWeek ?? []),
            $type->isEveryNDays() => "Cada {$snap->intervalDays} días",
            $type->isNone() => $snap->specificDate !== null ? "Fecha: {$snap->specificDate}" : '',
            default => '',
        };

        return implode(' | ', array_filter([
            "Recurrencia: {$rec}",
            "Hora: {$time}",
            $extra !== '' ? $extra : null,
            'Activo: '.($snap->isActive ? 'Sí' : 'No'),
        ]));
    }
}
