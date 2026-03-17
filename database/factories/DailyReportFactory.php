<?php

namespace Database\Factories;

use App\Enums\Mood;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    protected $model = DailyReport::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_date' => now()->toDateString(),
            'notes' => null,
            'mood' => null,
        ];
    }

    public function withMood(Mood $mood = Mood::Good): static
    {
        return $this->state(['mood' => $mood->value]);
    }

    public function withNotes(string $notes = 'Buen día'): static
    {
        return $this->state(['notes' => $notes]);
    }

    public function forDate(string $date): static
    {
        return $this->state(['report_date' => $date]);
    }
}
