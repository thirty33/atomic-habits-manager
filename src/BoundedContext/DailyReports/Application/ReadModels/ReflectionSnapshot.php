<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

/**
 * Read model for the user's most recent reflection (notes + mood + when).
 */
final readonly class ReflectionSnapshot
{
    public function __construct(
        public int $dailyReportId,
        public string $reportDate,
        public ?string $mood,
        public ?string $notes,
        public string $createdAt,
    ) {}
}
