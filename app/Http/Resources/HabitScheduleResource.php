<?php

namespace App\Http\Resources;

use App\Enums\RecurrenceType;
use App\Models\HabitSchedule;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HabitSchedule
 */
class HabitScheduleResource extends JsonResource
{
    private FormActionGenerator $formActionGenerator;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->formActionGenerator = new FormActionGenerator;
    }

    public function toArray(Request $request): array
    {
        return [
            'habit_schedule_id' => $this->habit_schedule_id,
            'recurrence_type' => $this->recurrence_type,
            'recurrence_type_label' => __(RecurrenceType::from($this->recurrence_type)->label()),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'days_of_week' => $this->days_of_week,
            'interval_days' => $this->interval_days,
            'specific_date' => $this->specific_date?->format('Y-m-d'),
            'starts_from' => $this->starts_from?->format('Y-m-d'),
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            'update_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habit-schedules.update', $this->habit_schedule_id),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
        ];
    }
}
