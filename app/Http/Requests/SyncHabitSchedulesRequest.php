<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceType;
use Core\BoundedContext\Habits\Application\Actions\FindHabit;
use Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncHabitSchedulesRequest extends FormRequest
{
    /**
     * Ownership check via the Habits use case + repository (no Eloquent here).
     * authorize() is resolved through the container, so it supports method injection.
     */
    public function authorize(FindHabit $findHabit): bool
    {
        try {
            $findHabit((int) $this->route('id'), (int) auth()->id());

            return true;
        } catch (HabitNotFound) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'schedules' => ['present', 'array'],
            'schedules.*.habit_schedule_id' => ['nullable', 'integer'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.recurrence_type' => ['required', Rule::in(array_column(RecurrenceType::cases(), 'value'))],
            'schedules.*.days_of_week' => ['nullable', 'array', 'required_if:schedules.*.recurrence_type,weekly'],
            'schedules.*.days_of_week.*' => ['integer', 'min:0', 'max:6'],
            'schedules.*.interval_days' => ['nullable', 'integer', 'min:1', 'required_if:schedules.*.recurrence_type,every_n_days'],
            'schedules.*.specific_date' => ['nullable', 'date', 'required_if:schedules.*.recurrence_type,none'],
            'schedules.*.starts_from' => ['nullable', 'date', 'required_unless:schedules.*.recurrence_type,none'],
            'schedules.*.ends_at' => ['nullable', 'date', 'after:schedules.*.starts_from', 'prohibited_if:schedules.*.recurrence_type,none'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'schedules.*.start_time.required' => 'La hora de inicio es requerida',
            'schedules.*.start_time.date_format' => 'El formato de hora debe ser HH:MM',
            'schedules.*.end_time.required' => 'La hora de fin es requerida',
            'schedules.*.end_time.date_format' => 'El formato de hora debe ser HH:MM',
            'schedules.*.end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio',
            'schedules.*.recurrence_type.required' => 'El tipo de recurrencia es requerido',
            'schedules.*.days_of_week.required_if' => 'Debes seleccionar al menos un día de la semana',
            'schedules.*.interval_days.required_if' => 'Debes especificar cada cuántos días',
            'schedules.*.specific_date.required_if' => 'Debes especificar la fecha',
            'schedules.*.starts_from.required_unless' => 'Debes especificar desde cuándo aplica la programación',
            'schedules.*.ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
        ];
    }
}
