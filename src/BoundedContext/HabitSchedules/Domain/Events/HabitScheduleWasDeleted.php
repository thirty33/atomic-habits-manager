<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\Events;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Domain event: an existing HabitSchedule has been (hard) deleted.
 *
 * Emitted before the row is removed from the database so the in-memory
 * aggregate still has access to its identifiers.
 */
final class HabitScheduleWasDeleted extends DomainEvent
{
    public function __construct(
        public readonly HabitScheduleId $habitScheduleId,
        public readonly HabitId $habitId,
        ?DateTimeImmutable $occurredOn = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredOn ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'habit_schedules.was_deleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'habit_schedule_id' => $this->habitScheduleId->value(),
            'habit_id' => $this->habitId->value(),
        ];
    }

    /**
     * @param  array{habit_schedule_id: int, habit_id: int}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            habitScheduleId: HabitScheduleId::from((int) $primitives['habit_schedule_id']),
            habitId: HabitId::from((int) $primitives['habit_id']),
        );
    }
}
