<?php

namespace App\Http\Requests;

use App\Enums\Mood;
use App\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = DailyReport::find($this->route('id'));

        return $report && $report->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'mood' => ['nullable', Rule::enum(Mood::class)],
        ];
    }
}
