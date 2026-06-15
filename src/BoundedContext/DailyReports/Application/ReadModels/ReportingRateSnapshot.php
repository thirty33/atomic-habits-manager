<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

/**
 * Share of days in a window for which the user filed a daily report.
 */
final readonly class ReportingRateSnapshot
{
    public function __construct(
        public int $percentage,
        public int $reportedDays,
        public int $windowDays,
    ) {}
}
