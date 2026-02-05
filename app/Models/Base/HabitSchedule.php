<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class HabitSchedule extends Model
{
    protected $table = 'habit_schedules';

    protected $primaryKey = 'habit_schedule_id';

    protected $fillable = [
        'habit_id',
        'previous_schedule_id',
        'chain_cue',
        'start_time',
        'end_time',
        'recurrence_type',
        'days_of_week',
        'interval_days',
        'specific_date',
        'starts_from',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'starts_from' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];
}