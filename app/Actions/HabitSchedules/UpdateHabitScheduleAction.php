<?php

namespace App\Actions\HabitSchedules;

use App\Actions\Contracts\UpdateAction;
use App\Models\HabitSchedule;

final class UpdateHabitScheduleAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        $schedule = HabitSchedule::findOrFail($id);
        $schedule->update([
            'start_time' => data_get($data, 'start_time'),
            'end_time' => data_get($data, 'end_time'),
            'recurrence_type' => data_get($data, 'recurrence_type'),
            'days_of_week' => data_get($data, 'days_of_week'),
            'interval_days' => data_get($data, 'interval_days'),
            'specific_date' => data_get($data, 'specific_date'),
            'starts_from' => data_get($data, 'starts_from'),
            'ends_at' => data_get($data, 'ends_at'),
        ]);
    }
}
