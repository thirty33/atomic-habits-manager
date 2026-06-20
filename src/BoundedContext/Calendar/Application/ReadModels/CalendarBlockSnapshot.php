<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Application\ReadModels;

final readonly class CalendarBlockSnapshot
{
    public function __construct(
        public int $habitOccurrenceId,
        public int $habitId,
        public ?int $habitScheduleId,
        public string $occurrenceDate,
        public string $endDate,
        public string $startTime,
        public string $endTime,
        public string $status,
        public ?string $habitName,
        public ?string $habitColor,
        public ?string $habitNature,
        public ?string $desireType,
        public ?bool $habitIsActive,
    ) {}
}
