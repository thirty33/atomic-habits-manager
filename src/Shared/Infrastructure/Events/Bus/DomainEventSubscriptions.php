<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Bus;

use Core\Shared\Domain\Events\DomainEvent;

/**
 * Registry of "which listeners react to which event".
 *
 * Lives separate from the bus because:
 *  - The SyncBus needs it to invoke listeners in process.
 *  - The OutboxBus does NOT need it on publish (it only writes to the
 *    table). But the HandleDomainEventJob DOES need it when consuming.
 *  - The SpyBus does not use it.
 *
 * That is why it is a separate object: each bus injects it only when
 * needed.
 */
final class DomainEventSubscriptions
{
    /** @var array<class-string<DomainEvent>, list<class-string>> */
    private array $map = [];

    /**
     * @param  class-string<DomainEvent>  $eventClass
     * @param  class-string  $listenerClass
     */
    public function register(string $eventClass, string $listenerClass): void
    {
        $this->map[$eventClass] ??= [];
        $this->map[$eventClass][] = $listenerClass;
    }

    /**
     * @return list<class-string>
     */
    public function listenersFor(DomainEvent $event): array
    {
        return $this->map[$event::class] ?? [];
    }

    /**
     * @return array<class-string<DomainEvent>, list<class-string>>
     */
    public function all(): array
    {
        return $this->map;
    }
}
