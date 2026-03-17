<?php

namespace App\Filters\DailyReport;

use App\Filters\BaseSorterFilter;

final class SorterFilter extends BaseSorterFilter
{
    public string $primaryKey = 'daily_report_id';

    public ?string $defaultSorterColumn = 'report_date';

    public array $availableColumnSorters = [
        'report_date', 'mood', 'created_at',
    ];
}
