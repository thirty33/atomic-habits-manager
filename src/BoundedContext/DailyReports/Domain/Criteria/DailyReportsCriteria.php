<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\Criteria;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Parameter object (Criteria pattern, PoEAA cap. 13) for paginated daily
 * report queries. Lives in Domain — all fields are Domain VOs or pure
 * primitives, no presentation concerns leak in.
 *
 * Used by DailyReportRepository::matching as a write-side-adjacent query
 * input. Separate from the Eloquent translator that turns it into SQL.
 */
final readonly class DailyReportsCriteria
{
    public function __construct(
        public UserId $userId,
        public ?ReportDate $fromDate,
        public ?ReportDate $toDate,
        public ?Mood $mood,
        public int $page,
        public int $perPage,
        public string $sortBy,
        public string $sortDir,
    ) {}
}
