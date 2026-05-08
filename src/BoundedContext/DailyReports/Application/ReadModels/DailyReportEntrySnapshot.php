<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

final readonly class DailyReportEntrySnapshot
{
    public function __construct(
        public int $dailyReportEntryId,
        public int $dailyReportId,
        public ?int $habitOccurrenceId,
        public ?int $habitId,
        public ?string $customActivity,
        public string $startTime,
        public string $endTime,
        public string $status,
        public ?string $completedAt,
        public ?string $notes,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}
}
