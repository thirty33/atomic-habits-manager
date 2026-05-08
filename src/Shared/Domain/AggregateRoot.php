<?php

declare(strict_types=1);

namespace Core\Shared\Domain;

use Core\Shared\Domain\Events\DomainEvent;

/**
 * Base class for all aggregate roots.
 *
 * Responsibilities:
 *  - Accumulate DomainEvents emitted during domain operations.
 *  - Expose them to the Repository after persistence (via pullDomainEvents).
 *  - NOT publish — that is the Repository's responsibility.
 *
 * Pattern: Vaughn Vernon, *Implementing DDD* ch.8 §"Aggregate Root Records
 * Domain Events".
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    /**
     * Accumulate an event in the internal list. Called from entity methods
     * when something worth notifying happens.
     */
    protected function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Returns the accumulated events and clears the internal list. Called
     * by the Repository after persisting state, to publish to the bus.
     *
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * Useful in tests: inspect accumulated events without consuming them.
     *
     * @return list<DomainEvent>
     */
    public function peekDomainEvents(): array
    {
        return $this->domainEvents;
    }
}
