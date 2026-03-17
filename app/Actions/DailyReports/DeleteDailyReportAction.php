<?php

namespace App\Actions\DailyReports;

use App\Actions\Contracts\DeleteAction;
use App\Models\DailyReport;

final class DeleteDailyReportAction implements DeleteAction
{
    public static function execute(int $id): void
    {
        DailyReport::findOrFail($id)->delete();
    }
}
