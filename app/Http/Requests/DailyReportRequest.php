<?php

namespace App\Http\Requests;

use App\Enums\Mood;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_date' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('daily_reports')
                    ->where('user_id', auth()->id())
                    ->ignore($this->route('id'), 'daily_report_id'),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'mood' => ['nullable', Rule::enum(Mood::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'report_date.before_or_equal' => 'No se puede crear un reporte para una fecha futura',
            'report_date.unique' => 'Ya existe un reporte para esta fecha',
        ];
    }
}
