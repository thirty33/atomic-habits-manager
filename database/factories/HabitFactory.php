<?php

namespace Database\Factories;

use App\Enums\DesireType;
use App\Enums\HabitNature;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    protected $model = Habit::class;

    public function definition(): array
    {
        $nature = fake()->randomElement(HabitNature::cases());

        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'color' => $nature->color(),
            'habit_nature' => $nature->value,
            'desire_type' => fake()->randomElement(DesireType::cases())->value,
            'implementation_intention' => null,
            'location' => null,
            'cue' => null,
            'reframe' => null,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function build(): static
    {
        return $this->state([
            'habit_nature' => HabitNature::BUILD->value,
            'color' => HabitNature::BUILD->color(),
        ]);
    }

    public function break(): static
    {
        return $this->state([
            'habit_nature' => HabitNature::BREAK->value,
            'color' => HabitNature::BREAK->color(),
        ]);
    }

    public function needsRebuild(): static
    {
        return $this->state(['needs_occurrence_rebuild' => true]);
    }

    public function rebuilt(): static
    {
        return $this->state(['needs_occurrence_rebuild' => false]);
    }
}
