<?php

namespace App\Ai\Strategies;

use App\Actions\HabitSchedules\CreateHabitScheduleAction;
use App\Actions\HabitSchedules\UpdateHabitScheduleAction;
use App\Ai\Contracts\UpdatableResource;
use App\Enums\HabitNature;
use App\Enums\RecurrenceType;
use App\Models\Habit;
use App\Models\HabitSchedule;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class HabitUpdateStrategy implements UpdatableResource
{
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
            'name' => $schema->string()
                ->description('Nuevo nombre del hábito.'),
            'habit_nature' => $schema->string()
                ->enum(['build', 'break'])
                ->description('build = adoptar, break = eliminar.'),
            'desire_type' => $schema->string()
                ->enum(['need', 'want', 'neutral'])
                ->description('need = lo necesito, want = lo quiero, neutral = no estoy seguro.'),
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
                ->description('Si el hábito está activo.'),
            'schedule_id' => $schema->integer()
                ->description('ID de la programación existente a modificar (obtenlo con list_resource antes de llamar este tool). Con schedule_id: solo envía los campos que quieres cambiar, los demás se conservan. Sin schedule_id: se crea una nueva programación para el hábito (schedule_recurrence_type, schedule_start_time y schedule_end_time son obligatorios).'),
            'schedule_recurrence_type' => $schema->string()
                ->enum(['none', 'daily', 'weekly', 'every_n_days'])
                ->description('Tipo de recurrencia. Obligatorio al crear nueva programación. Opcional al actualizar (solo si quieres cambiar la recurrencia). none=fecha única, daily=todos los días, weekly=días específicos (requiere schedule_days_of_week), every_n_days=cada N días (requiere schedule_interval_days).'),
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

    public function update(int $userId, int $id, array $data): string
    {
        $habit = Habit::where('user_id', $userId)->findOrFail($id);

        $updated = [];

        $habitFields = ['name', 'habit_nature', 'desire_type', 'description', 'implementation_intention', 'location', 'cue', 'reframe', 'is_active'];
        $habitData = array_intersect_key($data, array_flip($habitFields));

        if (! empty($habitData)) {
            if (isset($habitData['habit_nature'])) {
                HabitNature::from($habitData['habit_nature']);
                $habitData['color'] = HabitNature::from($habitData['habit_nature'])->color();
            }

            $habit->update($habitData);
            $updated[] = 'hábito';
        }

        $scheduleFields = ['schedule_recurrence_type', 'schedule_start_time', 'schedule_end_time', 'schedule_days_of_week', 'schedule_interval_days', 'schedule_specific_date'];
        $hasScheduleFields = ! empty(array_intersect_key($data, array_flip($scheduleFields)));

        if ($hasScheduleFields) {
            if (isset($data['schedule_recurrence_type'])) {
                RecurrenceType::from($data['schedule_recurrence_type']);
            }

            $scheduleData = $this->buildScheduleData($data);

            if (isset($data['schedule_id'])) {
                $schedule = HabitSchedule::findOrFail((int) $data['schedule_id']);

                if ($schedule->habit_id !== $habit->habit_id) {
                    return 'Error: la programación no pertenece a este hábito.';
                }

                UpdateHabitScheduleAction::execute($schedule->habit_schedule_id, $scheduleData);
                $updated[] = "programación ID {$schedule->habit_schedule_id}";
            } else {
                $schedule = CreateHabitScheduleAction::execute(
                    array_merge(['habit_id' => $habit->habit_id], $scheduleData)
                );
                $updated[] = "nueva programación ID {$schedule->habit_schedule_id}";
            }
        }

        if (empty($updated)) {
            return 'No se proporcionaron campos para actualizar.';
        }

        return 'Actualizado: '.implode(', ', $updated).'.';
    }

    private function buildScheduleData(array $data): array
    {
        $scheduleData = [];

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
