<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportNotes;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;
use DomainException;

final class DailyReport
{
    private ?DailyReportId $id = null;

    /** @var DailyReportEntry[] entries marked for deletion at next save(). */
    private array $removedEntries = [];

    private function __construct(
        private UserId $userId,
        private ReportDate $reportDate,
        private ?ReportNotes $notes,
        private ?Mood $mood,
        private DailyReportEntries $entries,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {}

    public static function create(UserId $userId, ReportDate $reportDate): self
    {
        return new self(
            $userId,
            $reportDate,
            null,
            null,
            DailyReportEntries::empty(),
            new DateTimeImmutable,
            null,
        );
    }

    public static function reconstitute(
        DailyReportId $id,
        UserId $userId,
        ReportDate $reportDate,
        ?ReportNotes $notes,
        ?Mood $mood,
        DailyReportEntries $entries,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): self {
        $report = new self(
            $userId,
            $reportDate,
            $notes,
            $mood,
            $entries,
            $createdAt,
            $updatedAt,
        );
        $report->id = $id;

        return $report;
    }

    public function id(): ?DailyReportId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function reportDate(): ReportDate
    {
        return $this->reportDate;
    }

    public function notes(): ?ReportNotes
    {
        return $this->notes;
    }

    public function mood(): ?Mood
    {
        return $this->mood;
    }

    public function entries(): DailyReportEntries
    {
        return $this->entries;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    /** @return DailyReportEntry[] entries removed from the aggregate since the last save. */
    public function pullRemovedEntries(): array
    {
        $removed = $this->removedEntries;
        $this->removedEntries = [];

        return $removed;
    }

    public function updateNotes(?ReportNotes $notes): void
    {
        $this->notes = $notes;
        $this->touch();
    }

    public function updateMood(?Mood $mood): void
    {
        $this->mood = $mood;
        $this->touch();
    }

    /**
     * Replace the full set of entries. Used by SaveDailyReportEntries Use Case
     * — the frontend always sends the complete day's set.
     *
     * Reconciliation:
     *  - entries with id present in $next AND in $this->entries → updated in place
     *  - entries without id in $next → new, added
     *  - entries with id in $this->entries but NOT in $next → marked for deletion
     *
     * @param  DailyReportEntry[]  $next
     */
    public function replaceEntries(array $next): void
    {
        $this->guardEntriesBelongHere($next);

        $existingById = [];
        foreach ($this->entries->all() as $entry) {
            if ($entry->isPersisted()) {
                $existingById[$entry->id()->value()] = $entry;
            }
        }

        $kept = [];
        $seenIds = [];

        foreach ($next as $entry) {
            if ($entry->isPersisted()) {
                $idValue = $entry->id()->value();
                $seenIds[$idValue] = true;

                if (! isset($existingById[$idValue])) {
                    throw new DomainException(sprintf(
                        'DailyReportEntry %d does not belong to DailyReport %d.',
                        $idValue,
                        $this->id?->value() ?? 0,
                    ));
                }

                $kept[] = $existingById[$idValue];
            } else {
                $kept[] = $entry;
            }
        }

        foreach ($existingById as $idValue => $existing) {
            if (! isset($seenIds[$idValue])) {
                $this->removedEntries[] = $existing;
            }
        }

        $this->entries = new DailyReportEntries($kept);
        $this->touch();
    }

    public function assignId(DailyReportId $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('DailyReport already has an ID.');
        }

        $this->id = $id;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }

    /** @param DailyReportEntry[] $entries */
    private function guardEntriesBelongHere(array $entries): void
    {
        if ($this->id === null) {
            return;
        }

        foreach ($entries as $entry) {
            if (! $entry->dailyReportId()->equals($this->id)) {
                throw new DomainException(sprintf(
                    'DailyReportEntry references daily_report_id %d but aggregate is %d.',
                    $entry->dailyReportId()->value(),
                    $this->id->value(),
                ));
            }
        }
    }
}
