<?php

namespace App\Models\Base;

use App\Casts\TimeCast;
use App\Enums\ReportEntryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReportEntry extends Model
{
    use HasFactory;

    protected $table = 'daily_report_entries';

    protected $primaryKey = 'daily_report_entry_id';

    protected $fillable = [
        'daily_report_id',
        'habit_occurrence_id',
        'habit_id',
        'custom_activity',
        'start_time',
        'end_time',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'status' => ReportEntryStatus::class,
        'completed_at' => 'datetime',
        'start_time' => TimeCast::class,
        'end_time' => TimeCast::class,
    ];
}
