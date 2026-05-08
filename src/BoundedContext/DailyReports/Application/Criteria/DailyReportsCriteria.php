<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Criteria;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

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
