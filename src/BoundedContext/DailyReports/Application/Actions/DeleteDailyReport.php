<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportNotFound;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;

final readonly class DeleteDailyReport
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(DailyReportId $id): void
    {
        $report = $this->repository->findWithEntries($id);

        if ($report === null) {
            throw DailyReportNotFound::withId($id);
        }

        $this->repository->delete($report);
    }
}
