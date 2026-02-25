<?php

namespace App\Actions\HabitSchedules;

use App\Actions\Contracts\UpdateAction;
use App\Models\HabitSchedule;

final class UpdateHabitScheduleAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        $allowed = ['start_time', 'end_time', 'recurrence_type', 'days_of_week', 'interval_days', 'specific_date', 'starts_from', 'ends_at'];

        $schedule = HabitSchedule::findOrFail($id);
        $schedule->update(array_intersect_key($data, array_flip($allowed)));
    }
}
