<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\CreatableResource;
use Core\BoundedContext\Habits\Application\Actions\CreateHabit;
use Core\BoundedContext\Habits\Application\DTOs\CreateHabitData;
use Core\BoundedContext\HabitSchedules\Application\Actions\CreateHabitSchedule;
use Core\BoundedContext\HabitSchedules\Application\DTOs\CreateHabitScheduleData;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class HabitCreateStrategy implements CreatableResource
{
    public function __construct(
        private readonly CreateHabit $createHabit,
        private readonly CreateHabitSchedule $createSchedule,
    ) {}

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
            'name', 'habit_nature', 'desire_type', 'description',
            'implementation_intention', 'location', 'cue', 'reframe',
            'is_active',
            'schedule_recurrence_type', 'schedule_start_time', 'schedule_end_time',
            'schedule_days_of_week', 'schedule_interval_days', 'schedule_specific_date',
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
            'description' => $schema->string()->description('Descripción del hábito.'),
            'implementation_intention' => $schema->string()->description('Cuándo, dónde y cómo se realizará el hábito.'),
            'location' => $schema->string()->description('Lugar donde se realizará el hábito.'),
            'cue' => $schema->string()->description('Señal o disparador del hábito.'),
            'reframe' => $schema->string()->description('Reformulación positiva del hábito.'),
            'is_active' => $schema->boolean()->description('Si el hábito está activo. Por defecto true. Solo enviar si el usuario indica explícitamente que quiere el hábito inactivo.'),
            'schedule_recurrence_type' => $schema->string()
                ->enum(['none', 'daily', 'weekly', 'every_n_days'])
                ->description('Tipo de recurrencia. Si lo envías, también debes enviar schedule_start_time y schedule_end_time. none=fecha única (requiere schedule_specific_date), daily=todos los días, weekly=días específicos (requiere schedule_days_of_week), every_n_days=cada N días (requiere schedule_interval_days).'),
            'schedule_start_time' => $schema->string()->description('Hora de inicio HH:MM. Si lo envías, debes enviar también schedule_end_time.'),
            'schedule_end_time' => $schema->string()->description('Hora de fin HH:MM. Obligatorio junto con schedule_start_time. Puede ser ANTERIOR a la hora de inicio para indicar que la sesión cruza la medianoche (ej. dormir de 23:00 a 07:00): en ese caso es UNA sola programación, NO la dividas en dos ni inviertas las horas. La única restricción es que no puede ser igual a schedule_start_time.'),
            'schedule_days_of_week' => $schema->string()->description('Días separados por coma usando números enteros: 0=domingo, 1=lunes, 2=martes, 3=miércoles, 4=jueves, 5=viernes, 6=sábado. Ejemplo lunes a viernes: "1,2,3,4,5". Obligatorio si schedule_recurrence_type=weekly.'),
            'schedule_interval_days' => $schema->integer()->description('Cada cuántos días repetir. Obligatorio si schedule_recurrence_type=every_n_days.'),
            'schedule_specific_date' => $schema->string()->description('Fecha YYYY-MM-DD. Obligatorio si schedule_recurrence_type=none.'),
        ];
    }

    public function create(int $userId, array $data): string
    {
        $habitResp = ($this->createHabit)(CreateHabitData::fromArray([
            'user_id' => $userId,
            'name' => $data['name'],
            'habit_nature' => $data['habit_nature'],
            'desire_type' => $data['desire_type'],
            'description' => $data['description'] ?? null,
            'implementation_intention' => $data['implementation_intention'] ?? null,
            'location' => $data['location'] ?? null,
            'cue' => $data['cue'] ?? null,
            'reframe' => $data['reframe'] ?? null,
        ]));

        $messages = ["Hábito '{$habitResp->name}' creado con ID {$habitResp->habitId}."];

        if (isset($data['schedule_recurrence_type'])) {
            $scheduleResp = ($this->createSchedule)(CreateHabitScheduleData::fromArray($this->buildScheduleData($habitResp->habitId, $data)));
            $messages[] = "Programación creada con ID {$scheduleResp->habitScheduleId}.";
        }

        return implode(' ', $messages);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildScheduleData(int $habitId, array $data): array
    {
        $scheduleData = [
            'habit_id' => $habitId,
            'start_time' => $data['schedule_start_time'] ?? '00:00',
            'end_time' => $data['schedule_end_time'] ?? '23:59',
            'recurrence_type' => $data['schedule_recurrence_type'],
        ];

        if (isset($data['schedule_interval_days'])) {
            $scheduleData['interval_days'] = (int) $data['schedule_interval_days'];
        }
        if (isset($data['schedule_specific_date'])) {
            $scheduleData['specific_date'] = (string) $data['schedule_specific_date'];
        }
        if (isset($data['schedule_days_of_week'])) {
            $scheduleData['days_of_week'] = array_map('intval', explode(',', (string) $data['schedule_days_of_week']));
        }

        return $scheduleData;
    }
}
