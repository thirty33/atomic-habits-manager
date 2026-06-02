<?php

namespace App\Http\Requests;

use App\Enums\DesireType;
use App\Enums\HabitNature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solo validación sintáctica del nombre. La unicidad por usuario es
            // una regla de dominio: la verifica el use case (CreateHabit/UpdateHabit)
            // vía el repositorio y, si falla, lanza HabitNameAlreadyTaken (mapeada a
            // 422 en bootstrap/app.php). Así no se cuenta con filas soft-deleted.
            'name' => [
                'required',
                'max:255',
            ],
            'description' => ['nullable', 'string'],
            'habit_nature' => ['required', Rule::in(array_column(HabitNature::cases(), 'value'))],
            'desire_type' => ['required', Rule::in(array_column(DesireType::cases(), 'value'))],
            'implementation_intention' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'cue' => ['nullable', 'string', 'max:255'],
            'reframe' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Nombre'),
            'description' => __('Descripcion'),
            'habit_nature' => __('Tipo de habito'),
            'desire_type' => __('Importancia'),
            'implementation_intention' => __('Intencion de implementacion'),
            'location' => __('Ubicacion'),
            'cue' => __('Señal'),
            'reframe' => __('Motivacion positiva'),
            'is_active' => __('Activo'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('El campo :attribute es obligatorio.'),
            'name.max' => __('El campo :attribute no debe exceder los :max caracteres.'),
            'habit_nature.required' => __('El campo :attribute es obligatorio.'),
            'habit_nature.in' => __('El campo :attribute debe ser "build" o "break".'),
            'desire_type.required' => __('El campo :attribute es obligatorio.'),
            'desire_type.in' => __('El campo :attribute debe ser "need", "want" o "neutral".'),
            'is_active.boolean' => __('El campo :attribute debe ser un booleano.'),
        ];
    }
}
