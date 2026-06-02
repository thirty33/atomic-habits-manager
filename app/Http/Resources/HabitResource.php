<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Carbon\Carbon;
use Core\BoundedContext\Habits\Application\Responses\HabitResponse;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\DesireType;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transform View (PoEAA cap 14, p.276) — segunda etapa del Two-Step View
 * (cap 14, p.279). Toma el HabitResponse (DTO de la primera etapa,
 * producido por el Use Case + assembler) y lo transforma a la
 * representacion JSON que el frontend del backoffice consume.
 *
 * Decisiones de presentacion que viven aqui:
 *   - Etiquetas i18n derivadas de los VOs.
 *   - Formato de fecha localizado.
 *   - URLs de acciones del controller.
 *   - ActionForm objects para el frontend.
 *
 * `$activeSchedule` se inyecta pre-cargado por el caller (ver
 * GetHabitsViewModelDdd::renderHabits) para evitar N+1. Es un snapshot —
 * read DTO del BC HabitSchedules — no un modelo Eloquent.
 *
 * Convive en paralelo con `HabitResource` (legacy, basada en el modelo
 * Eloquent) hasta que `HabitController` se migre al flujo DDD. Ese día,
 * el legacy se elimina y este se renombra.
 */
final class HabitResource extends JsonResource
{
    private FormActionGenerator $formActionGenerator;

    /**
     * @param  list<HabitScheduleSnapshot>  $schedules  Active schedules of the habit.
     */
    public function __construct(
        HabitResponse $resource,
        private readonly ?HabitScheduleSnapshot $activeSchedule = null,
        private readonly array $schedules = [],
    ) {
        parent::__construct($resource);
        $this->formActionGenerator = new FormActionGenerator;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HabitResponse $r */
        $r = $this->resource;

        return [
            'pk_name' => 'habit_id',
            'habit_id' => $r->habitId,
            'name' => $r->name,
            'description' => $r->description,
            'color' => $r->color,
            'habit_nature' => $r->habitNature,
            'habit_nature_label' => __(HabitNature::from($r->habitNature)->label()),
            'desire_type' => $r->desireType,
            'desire_type_label' => __(DesireType::from($r->desireType)->label()),
            'implementation_intention' => $r->implementationIntention,
            'location' => $r->location,
            'cue' => $r->cue,
            'reframe' => $r->reframe,
            'is_active' => $r->isActive,
            'created_at' => $r->createdAt !== null
                ? Carbon::parse($r->createdAt)->format('Y-m-d')
                : null,
            'created_at_iso_format_ll' => $r->createdAt !== null
                ? Carbon::parse($r->createdAt)->isoFormat('LL')
                : null,
            'update_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habits.update', $r->habitId),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
            'delete_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habits.destroy', $r->habitId),
                    method: FormActionGenerator::HTTP_METHOD_DELETE,
                )
            )->getActionForm(),
            'schedules_sync_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.habits.schedules.sync', $r->habitId),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
            'active_schedule' => $this->activeSchedule !== null
                ? (new ActiveScheduleResource($this->activeSchedule))->resolve()
                : null,
            'schedules' => array_map(
                fn (HabitScheduleSnapshot $schedule) => (new ActiveScheduleResource($schedule))->resolve(),
                $this->schedules,
            ),
        ];
    }
}
