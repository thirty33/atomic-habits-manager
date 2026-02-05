<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitOccurrence extends Base\HabitOccurrence
{
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habit_id', 'habit_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(HabitSchedule::class, 'habit_schedule_id', 'habit_schedule_id');
    }
}