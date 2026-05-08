<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

final readonly class DailyReportSnapshot
{
    /**
     * @param  DailyReportEntrySnapshot[]  $entries
     */
    public function __construct(
        public int $dailyReportId,
        public int $userId,
        public string $reportDate,
        public ?string $notes,
        public ?string $mood,
        public array $entries,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}
}
