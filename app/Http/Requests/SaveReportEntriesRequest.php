<?php

namespace App\Http\Requests;

use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReportEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = DailyReport::find($this->route('id'));

        return $report && $report->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'entries' => ['required', 'array'],
            'entries.*.daily_report_entry_id' => ['nullable', 'integer'],
            'entries.*.habit_occurrence_id' => ['nullable', 'integer', 'exists:habit_occurrences,habit_occurrence_id'],
            'entries.*.habit_id' => ['nullable', 'integer', 'exists:habits,habit_id'],
            'entries.*.custom_activity' => ['nullable', 'string', 'max:255'],
            'entries.*.start_time' => ['required', 'date_format:H:i'],
            'entries.*.end_time' => ['required', 'date_format:H:i', 'after:entries.*.start_time'],
            'entries.*.status' => ['required', Rule::enum(ReportEntryStatus::class)],
            'entries.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.*.start_time.required' => 'La hora de inicio es obligatoria',
            'entries.*.end_time.required' => 'La hora de fin es obligatoria',
            'entries.*.end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio',
            'entries.*.status.required' => 'El estado es obligatorio',
        ];
    }
}
