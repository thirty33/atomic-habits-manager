<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Bus;

use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Domain\Events\DomainEvent;
use Psr\Container\ContainerInterface;

/**
 * In-process synchronous bus. Resolves each listener from the container
 * and invokes it immediately.
 *
 * Use cases:
 *  - Local development when immediate effect is desired.
 *  - Small apps with low traffic that can afford the synchronous cost.
 *  - Integration tests where the full flow event → listener should be
 *    verified without running a worker.
 *
 * NOT for production in this app: if a listener is slow (e.g. regenerating
 * 365 occurrences) it blocks the HTTP request.
 */
final readonly class InMemorySyncDomainEventBus implements DomainEventBus
{
    public function __construct(
        private DomainEventSubscriptions $subscriptions,
        private ContainerInterface $container,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions->listenersFor($event) as $listenerClass) {
                $listener = $this->container->get($listenerClass);
                $listener($event);
            }
        }
    }
}
