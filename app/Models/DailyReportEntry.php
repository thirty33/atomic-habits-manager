<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportEntry extends Base\DailyReportEntry
{
    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id', 'daily_report_id');
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(HabitOccurrence::class, 'habit_occurrence_id', 'habit_occurrence_id');
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habit_id', 'habit_id');
    }
}
