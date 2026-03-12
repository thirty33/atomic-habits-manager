<?php

namespace Database\Factories;

use App\Enums\RecurrenceType;
use App\Models\Habit;
use App\Models\HabitSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HabitSchedule>
 */
class HabitScheduleFactory extends Factory
{
    protected $model = HabitSchedule::class;

    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => RecurrenceType::DAILY->value,
            'days_of_week' => null,
            'interval_days' => null,
            'specific_date' => null,
            'starts_from' => now()->toDateString(),
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state([
            'recurrence_type' => RecurrenceType::DAILY->value,
        ]);
    }

    public function weekly(array $days = [1, 2, 3, 4, 5]): static
    {
        return $this->state([
            'recurrence_type' => RecurrenceType::WEEKLY->value,
            'days_of_week' => $days,
        ]);
    }

    public function everyNDays(int $n = 3): static
    {
        return $this->state([
            'recurrence_type' => RecurrenceType::EVERY_N_DAYS->value,
            'interval_days' => $n,
        ]);
    }

    public function oneTime(string $date): static
    {
        return $this->state([
            'recurrence_type' => RecurrenceType::NONE->value,
            'specific_date' => $date,
            'starts_from' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
