<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Responses;

use Core\BoundedContext\DailyReports\Application\ReadModels\DailyReportEntrySnapshot;
use Core\BoundedContext\DailyReports\Domain\DailyReportEntry;

final readonly class DailyReportEntryResponse
{
    public function __construct(public DailyReportEntrySnapshot $snapshot) {}

    public static function from(DailyReportEntry $entry): self
    {
        return new self(new DailyReportEntrySnapshot(
            dailyReportEntryId: $entry->id()?->value() ?? 0,
            dailyReportId: $entry->dailyReportId()->value(),
            habitOccurrenceId: $entry->habitOccurrenceId()?->value(),
            habitId: $entry->habitId()?->value(),
            customActivity: $entry->customActivity()?->value(),
            startTime: $entry->time()->startTime(),
            endTime: $entry->time()->endTime(),
            status: $entry->status()->value(),
            completedAt: $entry->completedAt()?->format('Y-m-d H:i:s'),
            notes: $entry->notes()?->value(),
            createdAt: $entry->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $entry->updatedAt()?->format('Y-m-d H:i:s'),
        ));
    }
}
