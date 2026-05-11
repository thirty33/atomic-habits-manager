<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Core\BoundedContext\DailyReports\Application\ReadModels\DailyReportSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyReportSnapshot
 */
final class DailyReportResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly FormActionGenerator $formActionGenerator = new FormActionGenerator,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var DailyReportSnapshot $snap */
        $snap = $this->resource;

        $entries = $snap->entries;
        $total = count($entries);
        $reported = 0;
        foreach ($entries as $entry) {
            if ($entry->status !== \App\Enums\ReportEntryStatus::Pending->value) {
                $reported++;
            }
        }

        return [
            'pk_name' => 'daily_report_id',
            'daily_report_id' => $snap->dailyReportId,
            'user_id' => $snap->userId,
            'report_date' => $snap->reportDate,
            'notes' => $snap->notes,
            'mood' => $snap->mood,
            'mood_label' => $snap->mood !== null
                ? __(\App\Enums\Mood::from($snap->mood)->emoji().' '.\App\Enums\Mood::from($snap->mood)->label())
                : '—',
            'entries_count' => $total,
            'entries_reported' => $reported,
            'progress_label' => $total > 0 ? "{$reported}/{$total} reportados" : 'Sin entradas',
            'is_complete' => $total > 0 && $reported === $total,
            'edit_url' => route('backoffice.daily-reports.edit', $snap->dailyReportId),
            'delete_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.daily-reports.destroy', $snap->dailyReportId),
                    method: FormActionGenerator::HTTP_METHOD_DELETE,
                )
            )->getActionForm(),
            'created_at' => $snap->createdAt,
            'updated_at' => $snap->updatedAt,
        ];
    }
}
