<?php

namespace App\Http\Resources;

use App\Models\HabitOccurrence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HabitOccurrence
 */
class HabitOccurrenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'habit_occurrence_id' => $this->habit_occurrence_id,
            'habit_id' => $this->habit_id,
            'habit_schedule_id' => $this->habit_schedule_id,
            'occurrence_date' => $this->occurrence_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'habit' => $this->whenLoaded('habit', fn () => [
                'habit_id' => $this->habit->habit_id,
                'name' => $this->habit->name,
                'color' => $this->habit->color,
                'habit_nature' => $this->habit->habit_nature->value,
                'habit_nature_label' => __($this->habit->habit_nature->label()),
                'desire_type' => $this->habit->desire_type->value,
                'desire_type_label' => __($this->habit->desire_type->label()),
                'is_active' => $this->habit->is_active,
            ]),
        ];
    }
}
