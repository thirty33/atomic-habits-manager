<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use DateTimeImmutable;

/**
 * Read-DTO of an outbox entry. Internal to the events infrastructure, not
 * a domain entity. Returned by OutboxRepository's read methods.
 */
final readonly class OutboxEntryDto
{
    public function __construct(
        public int $id,
        public string $eventId,
        public string $eventName,
        public string $payload,
        public DateTimeImmutable $occurredOn,
        public int $attempts,
        public ?DateTimeImmutable $dispatchedAt = null,
        public ?DateTimeImmutable $completedAt = null,
        public ?DateTimeImmutable $failedAt = null,
    ) {}
}
