<?php

namespace Database\Factories;

use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use App\Models\DailyReportEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReportEntry>
 */
class DailyReportEntryFactory extends Factory
{
    protected $model = DailyReportEntry::class;

    public function definition(): array
    {
        return [
            'daily_report_id' => DailyReport::factory(),
            'habit_occurrence_id' => null,
            'habit_id' => null,
            'custom_activity' => null,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => ReportEntryStatus::Pending->value,
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ReportEntryStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(['status' => ReportEntryStatus::Partial->value]);
    }

    public function notCompleted(): static
    {
        return $this->state(['status' => ReportEntryStatus::NotCompleted->value]);
    }

    public function skipped(): static
    {
        return $this->state(['status' => ReportEntryStatus::Skipped->value]);
    }

    public function freeActivity(string $name = 'Actividad libre'): static
    {
        return $this->state([
            'habit_id' => null,
            'custom_activity' => $name,
        ]);
    }
}
