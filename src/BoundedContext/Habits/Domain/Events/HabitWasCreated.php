<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain\Events;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class HabitWasCreated extends DomainEvent
{
    public function __construct(
        public readonly HabitId $habitId,
        public readonly UserId $userId,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(HabitId $habitId, UserId $userId): self
    {
        return new self(
            habitId: $habitId,
            userId: $userId,
            occurredAt: new DateTimeImmutable,
            eventId: bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'habits.was_created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'habit_id' => $this->habitId->value(),
            'user_id' => $this->userId->value(),
        ];
    }

    /**
     * @param  array{habit_id: int, user_id: int}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            habitId: HabitId::from((int) $primitives['habit_id']),
            userId: UserId::from((int) $primitives['user_id']),
        );
    }
}
