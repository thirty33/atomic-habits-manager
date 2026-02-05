<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HabitSchedule extends Base\HabitSchedule
{
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habit_id', 'habit_id');
    }

    public function previousSchedule(): BelongsTo
    {
        return $this->belongsTo(HabitSchedule::class, 'previous_schedule_id', 'habit_schedule_id');
    }

    public function nextSchedules(): HasMany
    {
        return $this->hasMany(HabitSchedule::class, 'previous_schedule_id', 'habit_schedule_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(HabitOccurrence::class, 'habit_schedule_id', 'habit_schedule_id');
    }
}