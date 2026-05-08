<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\CustomActivity;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportEntryId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryNotes;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryStatus;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryTime;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use DateTimeImmutable;
use DomainException;

/**
 * Child entity inside the DailyReport aggregate.
 *
 * NEVER instantiate or mutate from outside the aggregate root. All access
 * to entries goes through DailyReport methods (replaceEntries, addEntry,
 * removeEntry).
 */
final class DailyReportEntry
{
    private ?DailyReportEntryId $id = null;

    private function __construct(
        private DailyReportId $dailyReportId,
        private ?HabitOccurrenceId $habitOccurrenceId,
        private ?HabitId $habitId,
        private ?CustomActivity $customActivity,
        private EntryTime $time,
        private EntryStatus $status,
        private ?DateTimeImmutable $completedAt,
        private ?EntryNotes $notes,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        DailyReportId $dailyReportId,
        ?HabitOccurrenceId $habitOccurrenceId,
        ?HabitId $habitId,
        ?CustomActivity $customActivity,
        EntryTime $time,
        EntryStatus $status,
        ?EntryNotes $notes,
    ): self {
        $now = new DateTimeImmutable;
        $completedAt = $status->isCompletedLike() ? $now : null;

        return new self(
            $dailyReportId,
            $habitOccurrenceId,
            $habitId,
            $customActivity,
            $time,
            $status,
            $completedAt,
            $notes,
            $now,
            null,
        );
    }

    public static function reconstitute(
        DailyReportEntryId $id,
        DailyReportId $dailyReportId,
        ?HabitOccurrenceId $habitOccurrenceId,
        ?HabitId $habitId,
        ?CustomActivity $customActivity,
        EntryTime $time,
        EntryStatus $status,
        ?DateTimeImmutable $completedAt,
        ?EntryNotes $notes,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): self {
        $entry = new self(
            $dailyReportId,
            $habitOccurrenceId,
            $habitId,
            $customActivity,
            $time,
            $status,
            $completedAt,
            $notes,
            $createdAt,
            $updatedAt,
        );
        $entry->id = $id;

        return $entry;
    }

    public function id(): ?DailyReportEntryId
    {
        return $this->id;
    }

    public function dailyReportId(): DailyReportId
    {
        return $this->dailyReportId;
    }

    public function habitOccurrenceId(): ?HabitOccurrenceId
    {
        return $this->habitOccurrenceId;
    }

    public function habitId(): ?HabitId
    {
        return $this->habitId;
    }

    public function customActivity(): ?CustomActivity
    {
        return $this->customActivity;
    }

    public function time(): EntryTime
    {
        return $this->time;
    }

    public function status(): EntryStatus
    {
        return $this->status;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function notes(): ?EntryNotes
    {
        return $this->notes;
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

    /**
     * Update mutable fields. Only the aggregate root should call this.
     */
    public function update(
        ?HabitOccurrenceId $habitOccurrenceId,
        ?HabitId $habitId,
        ?CustomActivity $customActivity,
        EntryTime $time,
        EntryStatus $status,
        ?EntryNotes $notes,
    ): void {
        $this->habitOccurrenceId = $habitOccurrenceId;
        $this->habitId = $habitId;
        $this->customActivity = $customActivity;
        $this->time = $time;

        $statusChanged = $status->value() !== $this->status->value();
        $this->status = $status;

        if ($statusChanged) {
            $this->completedAt = $status->isCompletedLike() ? new DateTimeImmutable : null;
        }

        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function assignId(DailyReportEntryId $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('DailyReportEntry already has an ID.');
        }
        $this->id = $id;
    }
}
