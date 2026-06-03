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
            'recurrence_label' => $this->shortRecurrenceLabel($s->recurrenceType),
            'start_time' => $this->trimTime($s->startTime),
            'end_time' => $this->trimTime($s->endTime),
            'time_range' => $this->trimTime($s->startTime).'–'.$this->trimTime($s->endTime),
            'detail' => $this->detailLine($s),
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

    /**
     * Short frequency label for the compact chip (logical screen, stage 1): the long
     * `recurrence_type_label` ("Algunos días de la semana") is too wide for the table chip.
     */
    private function shortRecurrenceLabel(string $type): string
    {
        return match ($type) {
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'every_n_days' => 'Cada N días',
            'none' => 'Fecha puntual',
            default => __(RecurrenceType::from($type)->label()),
        };
    }

    /**
     * Recurrence-specific detail line (logical screen, stage 1). Lives here — in the
     * Transform View — so the frontend renders it without re-deriving anything: daily →
     * "Todos los días", weekly → single-letter day list, every-N-days → "Cada N días",
     * one-off → the specific date.
     */
    private function detailLine(HabitScheduleSnapshot $s): ?string
    {
        return match ($s->recurrenceType) {
            'daily' => 'Todos los días',
            'weekly' => $this->weekdayLetters($s->daysOfWeek),
            'every_n_days' => $s->intervalDays ? 'Cada '.$s->intervalDays.' días' : null,
            'none' => $s->specificDate,
            default => null,
        };
    }

    /**
     * @param  list<int>|null  $days
     */
    private function weekdayLetters(?array $days): ?string
    {
        if (empty($days)) {
            return null;
        }

        $letters = [0 => 'D', 1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S'];

        return collect($days)
            ->map(fn ($day) => $letters[$day] ?? $day)
            ->implode(' · ');
    }
}
