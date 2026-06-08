<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Core\BoundedContext\HabitOccurrences\Application\ReadModels\HabitOccurrenceSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HabitOccurrenceSnapshot
 */
class HabitOccurrenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var HabitOccurrenceSnapshot $snap */
        $snap = $this->resource;

        return [
            'habit_occurrence_id' => $snap->habitOccurrenceId,
            'habit_id' => $snap->habitId,
            'habit_schedule_id' => $snap->habitScheduleId,
            'occurrence_date' => $snap->occurrenceDate,
            'end_date' => $snap->endDate,
            'start_time' => $snap->startTime,
            'end_time' => $snap->endTime,
            'habit' => $snap->habitName === null ? null : [
                'habit_id' => $snap->habitId,
                'name' => $snap->habitName,
                'color' => $snap->habitColor,
                'habit_nature' => $snap->habitNature,
                'habit_nature_label' => __(\App\Enums\HabitNature::from($snap->habitNature)->label()),
                'desire_type' => $snap->desireType,
                'desire_type_label' => __(\App\Enums\DesireType::from($snap->desireType)->label()),
                'is_active' => $snap->habitIsActive,
            ],
        ];
    }
}
