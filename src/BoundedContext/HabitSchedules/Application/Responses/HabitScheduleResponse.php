<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Responses;

use Core\BoundedContext\HabitSchedules\Domain\HabitSchedule;

final readonly class HabitScheduleResponse
{
    public function __construct(
        public int $habitScheduleId,
        public int $habitId,
        public ?int $previousScheduleId,
        public ?string $chainCue,
        public string $startTime,
        public string $endTime,
        public string $recurrenceType,
        public ?array $daysOfWeek,
        public ?int $intervalDays,
        public ?string $specificDate,
        public string $startsFrom,
        public ?string $endsAt,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromHabitSchedule(HabitSchedule $schedule): self
    {
        $habitScheduleId = $schedule->habitScheduleId();

        if ($habitScheduleId === null) {
            throw new \LogicException('Cannot build HabitScheduleResponse from a HabitSchedule without id.');
        }

        return new self(
            habitScheduleId: $habitScheduleId->value(),
            habitId: $schedule->habitId()->value(),
            previousScheduleId: $schedule->previousScheduleId()?->value(),
            chainCue: $schedule->chainCue()?->value(),
            startTime: $schedule->timeRange()->startTime,
            endTime: $schedule->timeRange()->endTime,
            recurrenceType: $schedule->recurrenceType()->value(),
            daysOfWeek: $schedule->daysOfWeek()?->value(),
            intervalDays: $schedule->intervalDays()?->value,
            specificDate: $schedule->specificDate(),
            startsFrom: $schedule->dateRange()->startsFrom,
            endsAt: $schedule->dateRange()->endsAt,
            isActive: $schedule->isActive(),
            createdAt: $schedule->createdAt()?->format(\DateTimeInterface::ATOM),
            updatedAt: $schedule->updatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'habit_schedule_id' => $this->habitScheduleId,
            'habit_id' => $this->habitId,
            'previous_schedule_id' => $this->previousScheduleId,
            'chain_cue' => $this->chainCue,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'recurrence_type' => $this->recurrenceType,
            'days_of_week' => $this->daysOfWeek,
            'interval_days' => $this->intervalDays,
            'specific_date' => $this->specificDate,
            'starts_from' => $this->startsFrom,
            'ends_at' => $this->endsAt,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
