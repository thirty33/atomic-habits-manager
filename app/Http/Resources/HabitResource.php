<?php

namespace App\Http\Resources;

use App\Enums\DesireType;
use App\Enums\HabitNature;
use App\Models\Habit;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Habit
 */
class HabitResource extends JsonResource
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
            'pk_name' => 'habit_id',
            'habit_id' => $this->habit_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'habit_nature' => $this->habit_nature,
            'habit_nature_label' => __(HabitNature::from($this->habit_nature)->label()),
            'desire_type' => $this->desire_type,
            'desire_type_label' => __(DesireType::from($this->desire_type)->label()),
            'implementation_intention' => $this->implementation_intention,
            'location' => $this->location,
            'cue' => $this->cue,
            'reframe' => $this->reframe,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d'),
            'created_at_iso_format_ll' => $this->created_at->isoFormat('LL'),
            'update_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habits.update', $this->habit_id),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
            'delete_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habits.destroy', $this->habit_id),
                    method: FormActionGenerator::HTTP_METHOD_DELETE,
                )
            )->getActionForm(),
            'active_schedule' => $this->whenLoaded('schedules', function () {
                $schedule = $this->schedules->where('is_active', true)->first();

                return $schedule ? new HabitScheduleResource($schedule) : null;
            }),
        ];
    }
}
