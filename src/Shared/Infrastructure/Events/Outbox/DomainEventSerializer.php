<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Core\Shared\Domain\Events\DomainEvent;

interface DomainEventSerializer
{
    /**
     * Serializes the event to a JSON string. Includes only the payload —
     * the event name is stored as a separate column in the outbox.
     */
    public function serialize(DomainEvent $event): string;

    /**
     * Reconstructs the event from an outbox entry's eventName and serialized
     * payload string.
     */
    public function deserialize(string $eventName, string $serializedPayload): DomainEvent;
}
