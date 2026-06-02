<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\DTOs;

final readonly class SyncHabitSchedulesData
{
    /**
     * @param  list<array<string, mixed>>  $schedules  Validated per-schedule arrays
     *                                                 (each may carry `habit_schedule_id`).
     */
    public function __construct(
        public int $habitId,
        public array $schedules,
    ) {}

    /**
     * @param  array{schedules?: list<array<string, mixed>>}  $data
     */
    public static function fromArray(int $habitId, array $data): self
    {
        return new self(
            habitId: $habitId,
            schedules: array_values($data['schedules'] ?? []),
        );
    }
}
