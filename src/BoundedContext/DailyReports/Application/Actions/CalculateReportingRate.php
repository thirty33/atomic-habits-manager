<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\DailyReportReader;
use Core\BoundedContext\DailyReports\Application\ReadModels\ReportingRateSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;

/**
 * Share of the last N days for which the user filed a daily report.
 */
final readonly class CalculateReportingRate
{
    public function __construct(private DailyReportReader $reports) {}

    public function __invoke(UserId $userId, int $windowDays = 30, ?string $today = null): ReportingRateSnapshot
    {
        $today ??= date('Y-m-d');
        $from = (new DateTimeImmutable($today))
            ->modify('-'.($windowDays - 1).' days')
            ->format('Y-m-d');

        $reported = count($this->reports->reportedDates($userId, $from, $today));

        return new ReportingRateSnapshot(
            percentage: $windowDays > 0 ? (int) round($reported / $windowDays * 100) : 0,
            reportedDays: $reported,
            windowDays: $windowDays,
        );
    }
}
