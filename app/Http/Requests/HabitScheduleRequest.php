<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HabitScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'habit_id' => ['required', 'exists:habits,habit_id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'recurrence_type' => ['required', Rule::in(array_column(RecurrenceType::cases(), 'value'))],
            'days_of_week' => ['nullable', 'array', 'required_if:recurrence_type,weekly'],
            'days_of_week.*' => ['integer', 'min:0', 'max:6'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'required_if:recurrence_type,every_n_days'],
            'specific_date' => ['nullable', 'date', 'required_if:recurrence_type,none'],
            'starts_from' => ['nullable', 'date', 'required_unless:recurrence_type,none'],
            'ends_at' => ['nullable', 'date', 'after:starts_from', 'prohibited_if:recurrence_type,none'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'La hora de inicio es requerida',
            'start_time.date_format' => 'El formato de hora debe ser HH:MM',
            'end_time.required' => 'La hora de fin es requerida',
            'end_time.date_format' => 'El formato de hora debe ser HH:MM',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio',
            'recurrence_type.required' => 'El tipo de recurrencia es requerido',
            'days_of_week.required_if' => 'Debes seleccionar al menos un día de la semana',
            'interval_days.required_if' => 'Debes especificar cada cuántos días',
            'interval_days.min' => 'El intervalo debe ser al menos 1 día',
            'specific_date.required_if' => 'Debes especificar la fecha',
            'starts_from.required_unless' => 'Debes especificar desde cuándo aplica la programación',
            'ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
        ];
    }
}