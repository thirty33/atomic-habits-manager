<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HabitOccurrence>
 */
class HabitOccurrenceFactory extends Factory
{
    protected $model = HabitOccurrence::class;

    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'habit_schedule_id' => HabitSchedule::factory(),
            'occurrence_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];
    }
}
