<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\RecurrenceType;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transform View (PoEAA cap 14, p.276) — Stage 2 del schedule activo.
 *
 * Toma el HabitScheduleSnapshot (read DTO del BC HabitSchedules — Stage 1
 * producido por EloquentHabitScheduleRepository) y lo transforma a la
 * representacion JSON del backoffice. Anade i18n del label de recurrencia,
 * formato HH:MM de horas (el snapshot trae HH:MM:SS porque el adapter usa
 * getAttributes() para esquivar TimeCast), y el ActionForm de update.
 *
 * Vive en presentacion porque todas estas decisiones (i18n, routing,
 * formato de wire) son presentacion, no dominio. El snapshot se queda
 * con primitivas puras; este Resource las decora.
 */
final class ActiveScheduleResource extends JsonResource
{
    private FormActionGenerator $formActionGenerator;

    public function __construct(HabitScheduleSnapshot $resource)
    {
        parent::__construct($resource);
        $this->formActionGenerator = new FormActionGenerator;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HabitScheduleSnapshot $s */
        $s = $this->resource;

        return [
            'habit_schedule_id' => $s->habitScheduleId,
            'recurrence_type' => $s->recurrenceType,
            'recurrence_type_label' => __(RecurrenceType::from($s->recurrenceType)->label()),
            'start_time' => $this->trimTime($s->startTime),
            'end_time' => $this->trimTime($s->endTime),
            'days_of_week' => $s->daysOfWeek,
            'interval_days' => $s->intervalDays,
            'specific_date' => $s->specificDate,
            'starts_from' => $s->startsFrom,
            'ends_at' => $s->endsAt,
            'update_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habit-schedules.update', $s->habitScheduleId),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
        ];
    }

    /**
     * El snapshot trae el time crudo desde la DB ('HH:MM:SS') porque el
     * adapter usa getAttributes() para esquivar TimeCast del modelo.
     * Aqui aplicamos el truncado de presentacion ('HH:MM').
     */
    private function trimTime(?string $time): ?string
    {
        return $time !== null ? substr($time, 0, 5) : null;
    }
}
