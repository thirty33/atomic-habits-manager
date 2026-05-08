<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Bus;

use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Domain\Events\DomainEvent;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;

/**
 * Production bus. Persists each event in the events_outbox table. It does
 * NOT invoke listeners — that is handled by HandleDomainEventJob, dispatched
 * by DispatchOutboxEntriesCommand.
 *
 * Guarantee: the INSERT into the outbox happens within the Repository's
 * transaction (which also wraps state changes). State ↔ event atomicity.
 */
final readonly class OutboxDomainEventBus implements DomainEventBus
{
    public function __construct(
        private OutboxRepository $outbox,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->outbox->append(array_values($events));
    }
}
