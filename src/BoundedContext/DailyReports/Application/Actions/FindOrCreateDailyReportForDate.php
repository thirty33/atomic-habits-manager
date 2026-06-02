<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\Responses\DailyReportResponse;
use Core\BoundedContext\DailyReports\Domain\DailyReport;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class FindOrCreateDailyReportForDate
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(UserId $userId, ReportDate $date): DailyReportResponse
    {
        $existing = $this->repository->findByUserAndDate($userId, $date);

        if ($existing !== null) {
            return DailyReportResponse::from($existing);
        }

        $report = DailyReport::create($userId, $date);
        $this->repository->save($report);

        return DailyReportResponse::from($report);
    }
}
