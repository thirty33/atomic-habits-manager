<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Responses;

use Core\BoundedContext\DailyReports\Application\ReadModels\DailyReportSnapshot;
use Core\BoundedContext\DailyReports\Domain\DailyReport;

final readonly class DailyReportResponse
{
    public function __construct(public DailyReportSnapshot $snapshot) {}

    public static function from(DailyReport $report): self
    {
        $entrySnapshots = [];
        foreach ($report->entries() as $entry) {
            $entrySnapshots[] = DailyReportEntryResponse::from($entry)->snapshot;
        }

        return new self(new DailyReportSnapshot(
            dailyReportId: $report->id()?->value() ?? 0,
            userId: $report->userId()->value(),
            reportDate: $report->reportDate()->value(),
            notes: $report->notes()?->value(),
            mood: $report->mood()?->value(),
            entries: $entrySnapshots,
            createdAt: $report->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $report->updatedAt()?->format('Y-m-d H:i:s'),
        ));
    }
}
