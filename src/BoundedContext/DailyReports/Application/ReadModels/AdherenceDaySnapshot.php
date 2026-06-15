<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

/**
 * Adherence for a single day: how many of that day's occurrences were
 * completed, as a percentage.
 */
final readonly class AdherenceDaySnapshot
{
    public function __construct(
        public string $date,
        public int $total,
        public int $completed,
        public int $percentage,
        public bool $isFuture,
    ) {}
}
