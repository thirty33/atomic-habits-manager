<?php

namespace App\Ai\Strategies;

use App\Actions\Habits\CreateHabitAction;
use App\Actions\HabitSchedules\CreateHabitScheduleAction;
use App\Ai\Contracts\CreatableResource;
use App\Enums\HabitNature;
use App\Enums\RecurrenceType;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class HabitCreateStrategy implements CreatableResource
{
    public function resourceName(): string
    {
        return 'habits';
    }

    public function resourceDescription(): string
    {
        return 'Hábitos atómicos del usuario. Cada hábito puede tener una programación opcional.';
    }

    public function requiredFields(): array
    {
        return ['name', 'habit_nature', 'desire_type'];
    }

    public function fieldNames(): array
    {
        return [
            'name',
            'habit_nature',
            'desire_type',
            'description',
            'implementation_intention',
            'location',
            'cue',
            'reframe',
            'is_active',
            'schedule_recurrence_type',
            'schedule_start_time',
            'schedule_end_time',
            'schedule_days_of_week',
            'schedule_interval_days',
            'schedule_specific_date',
        ];
    }

    public function schemaFields(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nombre del hábito.')
                ->required(),
            'habit_nature' => $schema->string()
                ->enum(['build', 'break'])
                ->description('build = adoptar un buen hábito, break = eliminar un mal hábito.')
                ->required(),
            'desire_type' => $schema->string()
                ->enum(['need', 'want', 'neutral'])
                ->description('need = lo necesito, want = lo quiero, neutral = no estoy seguro.')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción del hábito.'),
            'implementation_intention' => $schema->string()
                ->description('Cuándo, dónde y cómo se realizará el hábito.'),
            'location' => $schema->string()
                ->description('Lugar donde se realizará el hábito.'),
            'cue' => $schema->string()
                ->description('Señal o disparador del hábito.'),
            'reframe' => $schema->string()
                ->description('Reformulación positiva del hábito.'),
            'is_active' => $schema->boolean()
                ->description('Si el hábito está activo. Por defecto true. Solo enviar si el usuario indica explícitamente que quiere el hábito inactivo.'),
            'schedule_recurrence_type' => $schema->string()
                ->enum(['none', 'daily', 'weekly', 'every_n_days'])
                ->description('Tipo de recurrencia. Si lo envías, también debes enviar schedule_start_time y schedule_end_time. none=fecha única (requiere schedule_specific_date), daily=todos los días, weekly=días específicos (requiere schedule_days_of_week), every_n_days=cada N días (requiere schedule_interval_days).'),
            'schedule_start_time' => $schema->string()
                ->description('Hora de inicio HH:MM. Si lo envías, debes enviar también schedule_end_time.'),
            'schedule_end_time' => $schema->string()
                ->description('Hora de fin HH:MM. Obligatorio junto con schedule_start_time.'),
            'schedule_days_of_week' => $schema->string()
                ->description('Días separados por coma usando números enteros: 0=domingo, 1=lunes, 2=martes, 3=miércoles, 4=jueves, 5=viernes, 6=sábado. Ejemplo lunes a viernes: "1,2,3,4,5". Obligatorio si schedule_recurrence_type=weekly.'),
            'schedule_interval_days' => $schema->integer()
                ->description('Cada cuántos días repetir. Obligatorio si schedule_recurrence_type=every_n_days.'),
            'schedule_specific_date' => $schema->string()
                ->description('Fecha YYYY-MM-DD. Obligatorio si schedule_recurrence_type=none.'),
        ];
    }

    public function create(int $userId, array $data): string
    {
        $this->validateEnums($data);

        $habit = CreateHabitAction::execute($data);

        $messages = ["Hábito '{$habit->name}' creado con ID {$habit->habit_id}."];

        if (isset($data['schedule_recurrence_type'])) {
            $schedule = CreateHabitScheduleAction::execute($this->buildScheduleData($habit->habit_id, $data));
            $messages[] = "Programación creada con ID {$schedule->habit_schedule_id}.";
        }

        return implode(' ', $messages);
    }

    private function validateEnums(array $data): void
    {
        if (isset($data['habit_nature'])) {
            HabitNature::from($data['habit_nature']);
        }

        if (isset($data['schedule_recurrence_type'])) {
            RecurrenceType::from($data['schedule_recurrence_type']);
        }
    }

    private function buildScheduleData(int $habitId, array $data): array
    {
        $scheduleData = ['habit_id' => $habitId];

        $map = [
            'schedule_recurrence_type' => 'recurrence_type',
            'schedule_start_time' => 'start_time',
            'schedule_end_time' => 'end_time',
            'schedule_interval_days' => 'interval_days',
            'schedule_specific_date' => 'specific_date',
        ];

        foreach ($map as $input => $field) {
            if (isset($data[$input])) {
                $scheduleData[$field] = $data[$input];
            }
        }

        if (isset($data['schedule_days_of_week'])) {
            $scheduleData['days_of_week'] = array_map(
                'intval',
                explode(',', $data['schedule_days_of_week'])
            );
        }

        return $scheduleData;
    }
}
