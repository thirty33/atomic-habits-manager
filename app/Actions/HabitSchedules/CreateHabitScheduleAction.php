<?php

namespace App\Actions\HabitSchedules;

use App\Actions\Contracts\CreateAction;
use App\Models\HabitSchedule;

final class CreateHabitScheduleAction implements CreateAction
{
    public static function execute(array $data = []): HabitSchedule
    {
        return HabitSchedule::create([
            'habit_id' => data_get($data, 'habit_id'),
            'start_time' => data_get($data, 'start_time'),
            'end_time' => data_get($data, 'end_time'),
            'recurrence_type' => data_get($data, 'recurrence_type'),
            'days_of_week' => data_get($data, 'days_of_week'),
            'interval_days' => data_get($data, 'interval_days'),
            'specific_date' => data_get($data, 'specific_date'),
            'starts_from' => data_get($data, 'starts_from', now()->toDateString()),
            'ends_at' => data_get($data, 'ends_at'),
            'is_active' => data_get($data, 'is_active', true),
        ]);
    }
}