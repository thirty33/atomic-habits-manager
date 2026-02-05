<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class HabitOccurrence extends Model
{
    protected $table = 'habit_occurrences';

    protected $primaryKey = 'habit_occurrence_id';

    protected $fillable = [
        'habit_id',
        'habit_schedule_id',
        'occurrence_date',
        'start_time',
        'end_time',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'completed_at' => 'datetime',
    ];
}