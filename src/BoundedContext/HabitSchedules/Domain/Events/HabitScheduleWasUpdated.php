<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\Events;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Domain event: an existing HabitSchedule has been updated.
 *
 * Carries `habitId` so downstream listeners in other BCs can react to the
 * change against the parent habit without re-querying.
 */
final class HabitScheduleWasUpdated extends DomainEvent
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
        return 'habit_schedules.was_updated';
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
