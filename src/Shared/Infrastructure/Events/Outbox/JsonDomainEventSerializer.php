<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Core\Shared\Domain\Events\DomainEvent;
use RuntimeException;

final readonly class JsonDomainEventSerializer implements DomainEventSerializer
{
    public function __construct(
        private DomainEventClassRegistry $registry,
    ) {}

    public function serialize(DomainEvent $event): string
    {
        return json_encode($event->toPrimitives(), JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $eventName, string $serializedPayload): DomainEvent
    {
        $class = $this->registry->classFor($eventName)
            ?? throw new RuntimeException("Unknown event name: {$eventName}");

        if (! method_exists($class, 'fromPrimitives')) {
            throw new RuntimeException("{$class} must implement static fromPrimitives(array)");
        }

        $payload = json_decode($serializedPayload, true, 512, JSON_THROW_ON_ERROR);

        return $class::fromPrimitives($payload);
    }
}
