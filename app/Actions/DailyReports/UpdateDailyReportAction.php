<?php

namespace App\Actions\DailyReports;

use App\Actions\Contracts\UpdateAction;
use App\Models\DailyReport;

final class UpdateDailyReportAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        DailyReport::findOrFail($id)->update([
            'notes' => data_get($data, 'notes'),
            'mood' => data_get($data, 'mood'),
        ]);
    }
}
