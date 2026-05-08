<?php

declare(strict_types=1);

namespace Core\Shared\Domain\Events;

use DateTimeImmutable;

/**
 * Base contract for all Domain Events. A DomainEvent captures a fact that
 * happened in the past, expressed in the language of the model.
 *
 * Conventions:
 *  - Names use past tense (HabitWasUpdated, not HabitUpdate).
 *  - Concrete classes are `final readonly`.
 *  - Constructors only accept VOs and primitives — events must be
 *    serializable cross-process (the queue worker in another process must
 *    be able to deserialize without access to the originating object).
 *  - `occurredOn()` is always present — supports replay and auditing.
 *  - `eventId()` is always present — for downstream idempotency.
 *
 * The `eventName()` method returns a stable identifier of the event type,
 * used by the serializer's class registry. Concrete classes typically
 * return a namespaced string like "habits.was_updated".
 */
abstract class DomainEvent
{
    public function __construct(
        protected DateTimeImmutable $occurredAt,
        protected string $eventId,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    /**
     * Stable type identifier used by the serializer class registry. Override
     * in concrete events to return values like "habits.was_updated".
     */
    abstract public static function eventName(): string;

    /**
     * Serializable payload (only primitives) used to persist the event in
     * the outbox table and as the queue message body. The serializer
     * reconstructs the event from this array via `fromPrimitives()`.
     *
     * @return array<string, mixed>
     */
    abstract public function toPrimitives(): array;
}
