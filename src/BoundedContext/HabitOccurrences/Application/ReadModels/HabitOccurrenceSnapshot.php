<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\ReadModels;

final readonly class HabitOccurrenceSnapshot
{
    public function __construct(
        public int $habitOccurrenceId,
        public int $habitId,
        public ?int $habitScheduleId,
        public string $occurrenceDate,
        public string $endDate,
        public string $startTime,
        public string $endTime,
        public ?string $habitName,
        public ?string $habitColor,
        public ?string $habitNature,
        public ?string $desireType,
        public ?bool $habitIsActive,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'habit_occurrence_id' => $this->habitOccurrenceId,
            'habit_id' => $this->habitId,
            'habit_schedule_id' => $this->habitScheduleId,
            'occurrence_date' => $this->occurrenceDate,
            'end_date' => $this->endDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'habit' => $this->habitName === null ? null : [
                'habit_id' => $this->habitId,
                'name' => $this->habitName,
                'color' => $this->habitColor,
                'habit_nature' => $this->habitNature,
                'desire_type' => $this->desireType,
                'is_active' => $this->habitIsActive,
            ],
        ];
    }
}
