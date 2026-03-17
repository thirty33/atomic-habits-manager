<?php

namespace App\Actions\DailyReports;

use App\Actions\Contracts\CreateAction;
use App\Models\DailyReport;

final class CreateDailyReportAction implements CreateAction
{
    public static function execute(array $data = []): DailyReport
    {
        return DailyReport::create([
            'user_id' => data_get($data, 'user_id'),
            'report_date' => data_get($data, 'report_date'),
            'notes' => data_get($data, 'notes'),
            'mood' => data_get($data, 'mood'),
        ]);
    }
}
