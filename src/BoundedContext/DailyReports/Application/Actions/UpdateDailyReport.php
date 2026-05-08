<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\DTOs\UpdateDailyReportData;
use Core\BoundedContext\DailyReports\Application\Responses\DailyReportResponse;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportNotFound;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportNotes;

final readonly class UpdateDailyReport
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(DailyReportId $id, UpdateDailyReportData $data): DailyReportResponse
    {
        $report = $this->repository->findWithEntries($id);

        if ($report === null) {
            throw DailyReportNotFound::withId($id);
        }

        $notes = $data->notes !== null ? ReportNotes::from($data->notes) : null;
        $mood = $data->mood !== null ? Mood::from($data->mood) : null;

        $report->updateNotes($notes);
        $report->updateMood($mood);

        $this->repository->save($report);

        return DailyReportResponse::from($report);
    }
}
