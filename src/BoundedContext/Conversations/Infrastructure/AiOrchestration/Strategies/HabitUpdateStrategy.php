<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\UpdatableResource;
use Core\BoundedContext\Habits\Application\Actions\UpdateHabit;
use Core\BoundedContext\Habits\Application\DTOs\UpdateHabitData;
use Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\Actions\CreateHabitSchedule;
use Core\BoundedContext\HabitSchedules\Application\Actions\UpdateHabitSchedule;
use Core\BoundedContext\HabitSchedules\Application\DTOs\CreateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\DTOs\UpdateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class HabitUpdateStrategy implements UpdatableResource
{
    public function __construct(
        private readonly HabitRepository $habits,
        private readonly HabitScheduleRepository $schedules,
        private readonly UpdateHabit $updateHabit,
        private readonly CreateHabitSchedule $createSchedule,
        private readonly UpdateHabitSchedule $updateSchedule,
    ) {}

    public function resourceName(): string
    {
        return 'habits';
    }

    public function resourceDescription(): string
    {
        return 'Hábitos atómicos y sus programaciones. Se puede actualizar el hábito, una programación existente o añadir una nueva programación.';
    }

    public function updatableFields(): array
    {
        return [
            'name', 'habit_nature', 'desire_type', 'description',
            'implementation_intention', 'location', 'cue', 'reframe', 'is_active',
            'schedule_id', 'schedule_recurrence_type', 'schedule_start_time',
            'schedule_end_time', 'schedule_days_of_week', 'schedule_interval_days',
            'schedule_specific_date',
        ];
    }

    public function fieldNames(): array
    {
        return $this->updatableFields();
    }

    public function schemaFields(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nuevo nombre del hábito.'),
            'habit_nature' => $schema->string()->enum(['build', 'break'])->description('build = adoptar, break = eliminar.'),
            'desire_type' => $schema->string()->enum(['need', 'want', 'neutral'])->description('need = lo necesito, want = lo quiero, neutral = no estoy seguro.'),
            'description' => $schema->string()->description('Descripción del hábito.'),
            'implementation_intention' => $schema->string()->description('Cuándo, dónde y cómo se realizará el hábito.'),
            'location' => $schema->string()->description('Lugar donde se realizará el hábito.'),
            'cue' => $schema->string()->description('Señal o disparador del hábito.'),
            'reframe' => $schema->string()->description('Reformulación positiva del hábito.'),
            'is_active' => $schema->boolean()->description('Si el hábito está activo.'),
            'schedule_id' => $schema->integer()->description('ID de la programación existente a modificar (obtenlo con list_resource antes de llamar este tool). Con schedule_id: solo envía los campos que quieres cambiar, los demás se conservan. Sin schedule_id: se crea una nueva programación para el hábito (schedule_recurrence_type, schedule_start_time y schedule_end_time son obligatorios).'),
            'schedule_recurrence_type' => $schema->string()->enum(['none', 'daily', 'weekly', 'every_n_days'])->description('Tipo de recurrencia. Obligatorio al crear nueva programación. Opcional al actualizar.'),
            'schedule_start_time' => $schema->string()->description('Hora de inicio HH:MM. Si lo envías, debes enviar también schedule_end_time.'),
            'schedule_end_time' => $schema->string()->description('Hora de fin HH:MM. Obligatorio junto con schedule_start_time.'),
            'schedule_days_of_week' => $schema->string()->description('Días separados por coma usando números enteros (0..6). Obligatorio si schedule_recurrence_type=weekly.'),
            'schedule_interval_days' => $schema->integer()->description('Cada cuántos días repetir. Obligatorio si schedule_recurrence_type=every_n_days.'),
            'schedule_specific_date' => $schema->string()->description('Fecha YYYY-MM-DD. Obligatorio si schedule_recurrence_type=none.'),
        ];
    }

    public function update(int $userId, int $id, array $data): string
    {
        $habitId = HabitId::from($id);
        $habit = $this->habits->findForUser($habitId, UserId::from($userId));

        if ($habit === null) {
            throw HabitNotFound::withId($habitId);
        }

        $updated = [];

        $habitFields = ['name', 'habit_nature', 'desire_type', 'description',
            'implementation_intention', 'location', 'cue', 'reframe', 'is_active'];
        $habitData = array_intersect_key($data, array_flip($habitFields));

        if ($habitData !== []) {
            ($this->updateHabit)(UpdateHabitData::fromArray([
                'habit_id' => $id,
                'user_id' => $userId,
                'name' => $habitData['name'] ?? $habit->name()->value(),
                'habit_nature' => $habitData['habit_nature'] ?? $habit->habitNature()->value(),
                'desire_type' => $habitData['desire_type'] ?? $habit->desireType()->value(),
                'description' => array_key_exists('description', $habitData)
                    ? $habitData['description']
                    : $habit->description()?->value(),
                'implementation_intention' => array_key_exists('implementation_intention', $habitData)
                    ? $habitData['implementation_intention']
                    : $habit->implementationIntention()?->value(),
                'location' => array_key_exists('location', $habitData)
                    ? $habitData['location']
                    : $habit->location()?->value(),
                'cue' => array_key_exists('cue', $habitData)
                    ? $habitData['cue']
                    : $habit->cue()?->value(),
                'reframe' => array_key_exists('reframe', $habitData)
                    ? $habitData['reframe']
                    : $habit->reframe()?->value(),
                'is_active' => $habitData['is_active'] ?? $habit->isActive(),
            ]));

            $updated[] = 'hábito';
        }

        $scheduleFields = ['schedule_recurrence_type', 'schedule_start_time', 'schedule_end_time',
            'schedule_days_of_week', 'schedule_interval_days', 'schedule_specific_date'];
        $hasScheduleFields = array_intersect_key($data, array_flip($scheduleFields)) !== [];

        if ($hasScheduleFields) {
            $payload = $this->buildScheduleData($data);

            if (isset($data['schedule_id'])) {
                $scheduleId = (int) $data['schedule_id'];
                $existing = $this->schedules->find(HabitScheduleId::from($scheduleId));

                if ($existing === null || $existing->habitId()->value() !== $id) {
                    return 'Error: la programación no pertenece a este hábito.';
                }

                ($this->updateSchedule)(UpdateHabitScheduleData::fromArray($scheduleId, [
                    'start_time' => $payload['start_time'] ?? $existing->timeRange()->start(),
                    'end_time' => $payload['end_time'] ?? $existing->timeRange()->end(),
                    'recurrence_type' => $payload['recurrence_type'] ?? $existing->recurrenceType()->value,
                    'days_of_week' => $payload['days_of_week'] ?? $existing->daysOfWeek()?->values(),
                    'interval_days' => $payload['interval_days'] ?? $existing->intervalDays()?->value(),
                    'specific_date' => $payload['specific_date'] ?? $existing->specificDate(),
                ]));

                $updated[] = "programación ID {$scheduleId}";
            } else {
                $resp = ($this->createSchedule)(CreateHabitScheduleData::fromArray(array_merge(
                    ['habit_id' => $id], $payload,
                )));
                $updated[] = "nueva programación ID {$resp->habitScheduleId}";
            }
        }

        if ($updated === []) {
            return 'No se proporcionaron campos para actualizar.';
        }

        return 'Actualizado: '.implode(', ', $updated).'.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildScheduleData(array $data): array
    {
        $payload = [];
        $map = [
            'schedule_recurrence_type' => 'recurrence_type',
            'schedule_start_time' => 'start_time',
            'schedule_end_time' => 'end_time',
            'schedule_interval_days' => 'interval_days',
            'schedule_specific_date' => 'specific_date',
        ];
        foreach ($map as $input => $field) {
            if (isset($data[$input])) {
                $payload[$field] = $data[$input];
            }
        }
        if (isset($data['schedule_days_of_week'])) {
            $payload['days_of_week'] = array_map('intval', explode(',', (string) $data['schedule_days_of_week']));
        }

        return $payload;
    }
}
