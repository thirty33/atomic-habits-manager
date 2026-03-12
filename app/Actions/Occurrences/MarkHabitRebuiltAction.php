<?php

namespace App\Actions\Occurrences;

use App\Models\Habit;

final class MarkHabitRebuiltAction
{
    /**
     * @param  array{habit_id: int}  $data
     */
    public static function execute(array $data = []): void
    {
        Habit::where('habit_id', data_get($data, 'habit_id'))
            ->update(['needs_occurrence_rebuild' => false]);
    }
}
