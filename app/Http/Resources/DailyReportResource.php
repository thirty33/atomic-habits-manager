<?php

namespace App\Http\Resources;

use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyReport
 */
class DailyReportResource extends JsonResource
{
    private FormActionGenerator $formActionGenerator;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->formActionGenerator = new FormActionGenerator;
    }

    public function toArray(Request $request): array
    {
        $entries = $this->whenLoaded('entries', fn () => $this->entries, collect());
        $total = $entries->count() ?: ($this->entries_count ?? 0);
        $reported = $entries->count() > 0
            ? $entries->where('status', '!=', ReportEntryStatus::Pending)->count()
            : ($this->entries_reported_count ?? 0);

        return [
            'pk_name' => 'daily_report_id',
            'daily_report_id' => $this->daily_report_id,
            'report_date' => $this->report_date?->format('d/m/Y'),
            'report_date_label' => $this->report_date ? $this->getRawOriginal('report_date') : null,
            'report_date_formatted' => $this->report_date?->isoFormat('LL'),
            'notes' => $this->notes,
            'mood' => $this->mood?->value,
            'mood_label' => $this->mood ? $this->mood->emoji().' '.$this->mood->label() : '—',
            'entries_count' => $total,
            'entries_reported' => $reported,
            'progress_label' => $total > 0 ? "{$reported}/{$total} reportados" : 'Sin entradas',
            'is_complete' => $total > 0 && $reported === $total,
            'edit_url' => route('backoffice.daily-reports.edit', $this->daily_report_id),
            'delete_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.daily-reports.destroy', $this->daily_report_id),
                    method: FormActionGenerator::HTTP_METHOD_DELETE,
                )
            )->getActionForm(),
        ];
    }
}
