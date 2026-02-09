<?php

namespace App\Models\Base;

use App\Casts\DateCast;
use App\Casts\TimeCast;
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
        'start_time' => TimeCast::class,
        'end_time' => TimeCast::class,
        'days_of_week' => 'array',
        'specific_date' => DateCast::class,
        'starts_from' => DateCast::class,
        'ends_at' => DateCast::class,
        'is_active' => 'boolean',
    ];
}
