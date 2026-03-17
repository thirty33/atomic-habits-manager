<?php

namespace App\Http\Resources;

use App\Models\DailyReportEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyReportEntry
 */
class DailyReportEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'daily_report_entry_id' => $this->daily_report_entry_id,
            'habit_occurrence_id' => $this->habit_occurrence_id,
            'habit_id' => $this->habit_id,
            'custom_activity' => $this->custom_activity,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status->value,
            'completed_at' => $this->completed_at?->toISOString(),
            'notes' => $this->notes,
            'habit' => $this->whenLoaded('habit', fn () => [
                'habit_id' => $this->habit->habit_id,
                'name' => $this->habit->name,
                'color' => $this->habit->color,
                'habit_nature' => $this->habit->habit_nature->value,
                'habit_nature_label' => __($this->habit->habit_nature->label()),
                'desire_type' => $this->habit->desire_type->value,
                'desire_type_label' => __($this->habit->desire_type->label()),
            ]),
        ];
    }
}
