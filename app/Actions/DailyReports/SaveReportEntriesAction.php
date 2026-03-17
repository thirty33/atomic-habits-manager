<?php

namespace App\Actions\DailyReports;

use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use App\Models\DailyReportEntry;
use Illuminate\Support\Facades\DB;

final class SaveReportEntriesAction
{
    /**
     * @param  array{
     *     daily_report_id: int,
     *     entries: array<int, array{
     *         daily_report_entry_id?: int|null,
     *         habit_occurrence_id?: int|null,
     *         habit_id?: int|null,
     *         custom_activity?: string|null,
     *         start_time: string,
     *         end_time: string,
     *         status: string,
     *         notes?: string|null,
     *     }>
     * }  $data
     */
    public static function execute(array $data = []): void
    {
        $reportId = data_get($data, 'daily_report_id');
        $entries = data_get($data, 'entries', []);

        DB::transaction(function () use ($reportId, $entries) {
            $report = DailyReport::findOrFail($reportId);
            $incomingIds = collect($entries)
                ->pluck('daily_report_entry_id')
                ->filter()
                ->values();

            // Delete entries removed by the user
            $report->entries()
                ->whereNotIn('daily_report_entry_id', $incomingIds)
                ->delete();

            // Upsert entries
            foreach ($entries as $entryData) {
                $entryId = data_get($entryData, 'daily_report_entry_id');
                $status = data_get($entryData, 'status', ReportEntryStatus::Pending->value);

                $attributes = [
                    'daily_report_id' => $reportId,
                    'habit_occurrence_id' => data_get($entryData, 'habit_occurrence_id'),
                    'habit_id' => data_get($entryData, 'habit_id'),
                    'custom_activity' => data_get($entryData, 'custom_activity'),
                    'start_time' => data_get($entryData, 'start_time'),
                    'end_time' => data_get($entryData, 'end_time'),
                    'status' => $status,
                    'completed_at' => $status === ReportEntryStatus::Completed->value ? now() : null,
                    'notes' => data_get($entryData, 'notes'),
                ];

                if ($entryId) {
                    DailyReportEntry::where('daily_report_entry_id', $entryId)
                        ->where('daily_report_id', $reportId)
                        ->update($attributes);
                } else {
                    DailyReportEntry::create($attributes);
                }
            }
        });
    }
}
