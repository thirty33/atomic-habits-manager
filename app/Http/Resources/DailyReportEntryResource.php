<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Core\BoundedContext\DailyReports\Application\ReadModels\DailyReportEntrySnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyReportEntrySnapshot
 */
final class DailyReportEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DailyReportEntrySnapshot $snap */
        $snap = $this->resource;

        return [
            'daily_report_entry_id' => $snap->dailyReportEntryId,
            'daily_report_id' => $snap->dailyReportId,
            'habit_occurrence_id' => $snap->habitOccurrenceId,
            'habit_id' => $snap->habitId,
            'custom_activity' => $snap->customActivity,
            'start_time' => $snap->startTime,
            'end_time' => $snap->endTime,
            'status' => $snap->status,
            'status_label' => __(\App\Enums\ReportEntryStatus::from($snap->status)->label()),
            'completed_at' => $snap->completedAt,
            'notes' => $snap->notes,
            'created_at' => $snap->createdAt,
            'updated_at' => $snap->updatedAt,
        ];
    }
}
