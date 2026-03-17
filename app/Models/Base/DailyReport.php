<?php

namespace App\Models\Base;

use App\Casts\DateCast;
use App\Enums\Mood;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    protected $table = 'daily_reports';

    protected $primaryKey = 'daily_report_id';

    protected $fillable = [
        'user_id',
        'report_date',
        'notes',
        'mood',
    ];

    protected $casts = [
        'report_date' => DateCast::class,
        'mood' => Mood::class,
    ];
}
