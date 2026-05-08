<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\ReadModels;

/**
 * Read DTO del BC HabitSchedules — fotografía inmutable de un schedule
 * activo, diseñada al rededor de las necesidades de la pantalla del
 * backoffice (PoEAA cap 15 DTO p.401: "se puede ver diferentes DTOs
 * correspondiendo a Web pages o GUI screens").
 *
 * Es un read model: vive en Application como cara pública del repo
 * para queries de solo-lectura. Cuando el BC HabitSchedules se migre a
 * DDD pleno (entidad + factories + invariantes), este snapshot se
 * mantiene — los Use Cases de write devolverán la entidad / un Response
 * distinto, este snapshot sigue sirviendo a las pantallas read-only.
 */
final readonly class HabitScheduleSnapshot
{
    public function __construct(
        public int $habitScheduleId,
        public int $habitId,
        public string $recurrenceType,
        public ?string $startTime,
        public ?string $endTime,
        /** @var ?list<int> */
        public ?array $daysOfWeek,
        public ?int $intervalDays,
        public ?string $specificDate,
        public ?string $startsFrom,
        public ?string $endsAt,
        public bool $isActive,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // El label i18n y la URL de update son decisiones de presentación
        // y se calculan en HTTP layer (ActiveScheduleResource), no aquí.
        // Este `toArray()` solo expone primitivas.
        return [
            'habit_schedule_id' => $this->habitScheduleId,
            'recurrence_type' => $this->recurrenceType,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'days_of_week' => $this->daysOfWeek,
            'interval_days' => $this->intervalDays,
            'specific_date' => $this->specificDate,
            'starts_from' => $this->startsFrom,
            'ends_at' => $this->endsAt,
            'is_active' => $this->isActive,
        ];
    }
}
