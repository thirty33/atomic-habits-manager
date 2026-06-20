<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DesireType;
use App\Enums\HabitNature;
use Core\BoundedContext\Calendar\Application\ReadModels\CalendarBlockSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalendarBlockSnapshot
 */
class CalendarBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CalendarBlockSnapshot $snap */
        $snap = $this->resource;

        return [
            'habit_occurrence_id' => $snap->habitOccurrenceId,
            'habit_id' => $snap->habitId,
            'habit_schedule_id' => $snap->habitScheduleId,
            'occurrence_date' => $snap->occurrenceDate,
            'end_date' => $snap->endDate,
            'start_time' => $snap->startTime,
            'end_time' => $snap->endTime,
            'status' => $snap->status,
            'status_label' => $this->statusLabel($snap->status),
            'habit' => $snap->habitName === null ? null : [
                'habit_id' => $snap->habitId,
                'name' => $snap->habitName,
                'color' => $snap->habitColor,
                'accent' => $this->accentFor($snap->habitNature),
                'habit_nature' => $snap->habitNature,
                'habit_nature_label' => $snap->habitNature !== null
                    ? __(HabitNature::from($snap->habitNature)->label())
                    : null,
                'desire_type' => $snap->desireType,
                'desire_type_label' => $snap->desireType !== null
                    ? __(DesireType::from($snap->desireType)->label())
                    : null,
                'is_active' => $snap->habitIsActive,
            ],
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'done' => __('Completado'),
            'partial' => __('Parcial'),
            'missed' => __('No cumplido'),
            'skipped' => __('Omitido'),
            default => __('Programado'),
        };
    }

    private function accentFor(?string $nature): string
    {
        return match ($nature) {
            'break' => 'danger',
            default => 'brand',
        };
    }
}
