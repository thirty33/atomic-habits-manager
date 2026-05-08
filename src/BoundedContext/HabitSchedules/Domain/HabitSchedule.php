<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasCreated;
use Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasDeleted;
use Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasUpdated;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\ChainCue;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DateRange;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\DaysOfWeek;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\IntervalDays;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\RecurrenceType;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\TimeRange;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

final class HabitSchedule extends AggregateRoot
{
    /**
     * Tracks whether this aggregate was reconstituted from persistence
     * (`fromPrimitives`) or freshly built via `create()`. Reconstituted
     * aggregates must not emit `HabitScheduleWasCreated` when their id is
     * assigned (it was already persisted before).
     */
    private bool $isFreshlyCreated;

    private function __construct(
        private ?HabitScheduleId $habitScheduleId,
        private HabitId $habitId,
        private ?HabitScheduleId $previousScheduleId,
        private ?ChainCue $chainCue,
        private TimeRange $timeRange,
        private RecurrenceType $recurrenceType,
        private ?DaysOfWeek $daysOfWeek,
        private ?IntervalDays $intervalDays,
        private ?string $specificDate,
        private DateRange $dateRange,
        private bool $isActive,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        bool $isFreshlyCreated,
    ) {
        $this->isFreshlyCreated = $isFreshlyCreated;
        $this->assertInvariants();
    }

    public static function create(
        HabitId $habitId,
        TimeRange $timeRange,
        RecurrenceType $recurrenceType,
        DateRange $dateRange,
        ?DaysOfWeek $daysOfWeek = null,
        ?IntervalDays $intervalDays = null,
        ?string $specificDate = null,
        ?ChainCue $chainCue = null,
        ?HabitScheduleId $previousScheduleId = null,
    ): self {
        return new self(
            habitScheduleId: null,
            habitId: $habitId,
            previousScheduleId: $previousScheduleId,
            chainCue: $chainCue,
            timeRange: $timeRange,
            recurrenceType: $recurrenceType,
            daysOfWeek: $daysOfWeek,
            intervalDays: $intervalDays,
            specificDate: $specificDate,
            dateRange: $dateRange,
            isActive: true,
            createdAt: null,
            updatedAt: null,
            isFreshlyCreated: true,
        );
    }

    /**
     * @param  ?list<int>  $daysOfWeek
     */
    public static function fromPrimitives(
        int $habitScheduleId,
        int $habitId,
        ?int $previousScheduleId,
        ?string $chainCue,
        string $startTime,
        string $endTime,
        string $recurrenceType,
        ?array $daysOfWeek,
        ?int $intervalDays,
        ?string $specificDate,
        string $startsFrom,
        ?string $endsAt,
        bool $isActive,
        ?string $createdAt,
        ?string $updatedAt,
    ): self {
        return new self(
            habitScheduleId: HabitScheduleId::from($habitScheduleId),
            habitId: HabitId::from($habitId),
            previousScheduleId: $previousScheduleId !== null ? HabitScheduleId::from($previousScheduleId) : null,
            chainCue: $chainCue !== null ? ChainCue::from($chainCue) : null,
            timeRange: TimeRange::from($startTime, $endTime),
            recurrenceType: RecurrenceType::from($recurrenceType),
            daysOfWeek: $daysOfWeek !== null ? DaysOfWeek::from($daysOfWeek) : null,
            intervalDays: $intervalDays !== null ? IntervalDays::from($intervalDays) : null,
            specificDate: $specificDate,
            dateRange: DateRange::from($startsFrom, $endsAt),
            isActive: $isActive,
            createdAt: $createdAt !== null ? new DateTimeImmutable($createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable($updatedAt) : null,
            isFreshlyCreated: false,
        );
    }

    public function update(
        TimeRange $timeRange,
        RecurrenceType $recurrenceType,
        DateRange $dateRange,
        ?DaysOfWeek $daysOfWeek = null,
        ?IntervalDays $intervalDays = null,
        ?string $specificDate = null,
        ?ChainCue $chainCue = null,
    ): void {
        $this->timeRange = $timeRange;
        $this->recurrenceType = $recurrenceType;
        $this->dateRange = $dateRange;
        $this->daysOfWeek = $daysOfWeek;
        $this->intervalDays = $intervalDays;
        $this->specificDate = $specificDate;
        $this->chainCue = $chainCue;

        $this->assertInvariants();

        if ($this->habitScheduleId !== null) {
            $this->record(new HabitScheduleWasUpdated(
                habitScheduleId: $this->habitScheduleId,
                habitId: $this->habitId,
            ));
        }
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function assignId(HabitScheduleId $id): void
    {
        if ($this->habitScheduleId !== null) {
            throw new \DomainException('HabitSchedule already has an ID.');
        }

        $this->habitScheduleId = $id;

        if ($this->isFreshlyCreated) {
            $this->record(new HabitScheduleWasCreated(
                habitScheduleId: $id,
                habitId: $this->habitId,
            ));
        }
    }

    /**
     * Marks this aggregate as scheduled for deletion. Emits
     * `HabitScheduleWasDeleted` so the event lands in the outbox before
     * the actual DELETE statement runs (the in-memory aggregate keeps its
     * id and habitId until the repository finishes the operation).
     */
    public function markForDeletion(): void
    {
        if ($this->habitScheduleId === null) {
            throw new \LogicException('Cannot mark for deletion a HabitSchedule without id.');
        }

        $this->record(new HabitScheduleWasDeleted(
            habitScheduleId: $this->habitScheduleId,
            habitId: $this->habitId,
        ));
    }

    public function habitScheduleId(): ?HabitScheduleId
    {
        return $this->habitScheduleId;
    }

    public function habitId(): HabitId
    {
        return $this->habitId;
    }

    public function previousScheduleId(): ?HabitScheduleId
    {
        return $this->previousScheduleId;
    }

    public function chainCue(): ?ChainCue
    {
        return $this->chainCue;
    }

    public function timeRange(): TimeRange
    {
        return $this->timeRange;
    }

    public function recurrenceType(): RecurrenceType
    {
        return $this->recurrenceType;
    }

    public function daysOfWeek(): ?DaysOfWeek
    {
        return $this->daysOfWeek;
    }

    public function intervalDays(): ?IntervalDays
    {
        return $this->intervalDays;
    }

    public function specificDate(): ?string
    {
        return $this->specificDate;
    }

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isNew(): bool
    {
        return $this->habitScheduleId === null;
    }

    public function hasId(): bool
    {
        return $this->habitScheduleId !== null;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function assertInvariants(): void
    {
        if ($this->recurrenceType->isWeekly() && $this->daysOfWeek === null) {
            throw new \InvalidArgumentException('weekly recurrence requires daysOfWeek.');
        }

        if ($this->recurrenceType->isEveryNDays() && $this->intervalDays === null) {
            throw new \InvalidArgumentException('every_n_days recurrence requires intervalDays.');
        }

        if ($this->recurrenceType->isNone()) {
            if ($this->specificDate === null) {
                throw new \InvalidArgumentException('none recurrence requires specificDate.');
            }

            if ($this->dateRange->endsAt !== null) {
                throw new \InvalidArgumentException('none recurrence forbids endsAt.');
            }
        }

        if (! $this->recurrenceType->isWeekly() && $this->daysOfWeek !== null) {
            throw new \InvalidArgumentException(sprintf(
                'daysOfWeek only allowed for weekly, got %s.',
                $this->recurrenceType->value(),
            ));
        }

        if (! $this->recurrenceType->isEveryNDays() && $this->intervalDays !== null) {
            throw new \InvalidArgumentException(sprintf(
                'intervalDays only allowed for every_n_days, got %s.',
                $this->recurrenceType->value(),
            ));
        }

        if (! $this->recurrenceType->isNone() && $this->specificDate !== null) {
            throw new \InvalidArgumentException(sprintf(
                'specificDate only allowed for none, got %s.',
                $this->recurrenceType->value(),
            ));
        }
    }
}
