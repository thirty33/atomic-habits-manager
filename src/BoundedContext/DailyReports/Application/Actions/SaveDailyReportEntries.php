<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\DTOs\DailyReportEntryData;
use Core\BoundedContext\DailyReports\Application\DTOs\SaveDailyReportEntriesData;
use Core\BoundedContext\DailyReports\Application\Responses\DailyReportResponse;
use Core\BoundedContext\DailyReports\Domain\DailyReportEntry;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportEntryNotFound;
use Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportNotFound;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\CustomActivity;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportEntryId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryNotes;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryStatus;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryTime;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;

final readonly class SaveDailyReportEntries
{
    public function __construct(private DailyReportRepository $repository) {}

    public function __invoke(DailyReportId $id, SaveDailyReportEntriesData $data): DailyReportResponse
    {
        $report = $this->repository->findWithEntries($id);

        if ($report === null) {
            throw DailyReportNotFound::withId($id);
        }

        $next = [];
        foreach ($data->entries as $entryData) {
            $next[] = $this->resolveEntry($report, $entryData);
        }

        $report->replaceEntries($next);
        $this->repository->save($report);

        return DailyReportResponse::from($report);
    }

    private function resolveEntry(
        \Core\BoundedContext\DailyReports\Domain\DailyReport $report,
        DailyReportEntryData $entryData,
    ): DailyReportEntry {
        $time = EntryTime::fromStrings($entryData->startTime, $entryData->endTime);
        $status = EntryStatus::from($entryData->status);
        $notes = $entryData->notes !== null ? EntryNotes::from($entryData->notes) : null;
        $custom = $entryData->customActivity !== null
            ? CustomActivity::from($entryData->customActivity)
            : null;
        $occurrenceId = $entryData->habitOccurrenceId !== null
            ? HabitOccurrenceId::from($entryData->habitOccurrenceId)
            : null;
        $habitId = $entryData->habitId !== null
            ? HabitId::from($entryData->habitId)
            : null;

        if ($entryData->dailyReportEntryId === null) {
            return DailyReportEntry::create(
                $report->id(),
                $occurrenceId,
                $habitId,
                $custom,
                $time,
                $status,
                $notes,
            );
        }

        $entryId = DailyReportEntryId::from($entryData->dailyReportEntryId);
        $existing = $report->entries()->findById($entryId);

        if ($existing === null) {
            throw DailyReportEntryNotFound::withId($entryId);
        }

        $existing->update($occurrenceId, $habitId, $custom, $time, $status, $notes);

        return $existing;
    }
}
