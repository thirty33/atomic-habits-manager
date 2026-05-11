<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsCriteria;
use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsPage;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;

final readonly class GetDailyReportsForUser
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(DailyReportsCriteria $criteria): DailyReportsPage
    {
        return $this->repository->matching($criteria);
    }
}
