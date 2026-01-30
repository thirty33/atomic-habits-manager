<?php

namespace App\Actions\Habits;

use App\Actions\Contracts\CreateAction;
use App\Enums\HabitNature;
use App\Models\Habit;

final class CreateHabitAction implements CreateAction
{
    public static function execute(array $data = []): Habit
    {
        return Habit::create([
            'user_id' => auth()->id(),
            'name' => data_get($data, 'name'),
            'description' => data_get($data, 'description'),
            'color' => HabitNature::from(data_get($data, 'habit_nature'))->color(),
            'habit_nature' => data_get($data, 'habit_nature'),
            'desire_type' => data_get($data, 'desire_type'),
            'implementation_intention' => data_get($data, 'implementation_intention'),
            'location' => data_get($data, 'location'),
            'cue' => data_get($data, 'cue'),
            'reframe' => data_get($data, 'reframe'),
            'is_active' => data_get($data, 'is_active', false),
        ]);
    }
}