<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DomainException;

final class HabitOccurrence extends AggregateRoot
{
    private ?HabitOccurrenceId $id = null;

    private function __construct(
        private HabitId $habitId,
        private OccurrenceDate $scheduledDate,
        private OccurrenceTime $timeWindow,
        private ?HabitScheduleId $scheduleId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {}

    public static function schedule(
        HabitId $habitId,
        OccurrenceDate $date,
        OccurrenceTime $timeWindow,
        ?HabitScheduleId $scheduleId = null,
    ): self {
        return new self(
            habitId: $habitId,
            scheduledDate: $date,
            timeWindow: $timeWindow,
            scheduleId: $scheduleId,
            createdAt: new DateTimeImmutable,
            updatedAt: null,
        );
    }

    public static function reconstitute(
        HabitOccurrenceId $id,
        HabitId $habitId,
        OccurrenceDate $scheduledDate,
        OccurrenceTime $timeWindow,
        ?HabitScheduleId $scheduleId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): self {
        $occurrence = new self(
            habitId: $habitId,
            scheduledDate: $scheduledDate,
            timeWindow: $timeWindow,
            scheduleId: $scheduleId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
        $occurrence->id = $id;

        return $occurrence;
    }

    public function id(): ?HabitOccurrenceId
    {
        return $this->id;
    }

    public function habitId(): HabitId
    {
        return $this->habitId;
    }

    public function scheduleId(): ?HabitScheduleId
    {
        return $this->scheduleId;
    }

    public function scheduledDate(): OccurrenceDate
    {
        return $this->scheduledDate;
    }

    /**
     * The calendar date on which the window ends. Equals the anchor for an
     * intra-day window, or the next day when the window crosses midnight.
     */
    public function endDate(): OccurrenceDate
    {
        if (! $this->timeWindow->crossesMidnight()) {
            return $this->scheduledDate;
        }

        return OccurrenceDate::fromString(
            $this->scheduledDate->date()->modify('+1 day')->format('Y-m-d'),
        );
    }

    public function timeWindow(): OccurrenceTime
    {
        return $this->timeWindow;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function isInThePast(?DateTimeImmutable $reference = null): bool
    {
        return $this->scheduledDate->isPast($reference);
    }

    public function assignId(HabitOccurrenceId $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('HabitOccurrence already has an id');
        }
        $this->id = $id;
    }

    public function detachSchedule(): void
    {
        $this->scheduleId = null;
        $this->updatedAt = new DateTimeImmutable;
    }
}
