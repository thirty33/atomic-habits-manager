<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Base\DailyReport
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DailyReportEntry::class, 'daily_report_id', 'daily_report_id');
    }
}
