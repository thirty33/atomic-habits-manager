<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\Responses\DailyReportResponse;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportNotFound;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class FindDailyReportWithEntries
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(DailyReportId $id, UserId $userId): DailyReportResponse
    {
        $report = $this->repository->findForUserWithEntries($id, $userId);

        if ($report === null) {
            throw DailyReportNotFound::withId($id);
        }

        return DailyReportResponse::from($report);
    }
}
