<?php

declare(strict_types=1);

namespace Core\Shared\Domain\Bus;

use Core\Shared\Domain\Events\DomainEvent;

/**
 * Domain port for publishing Domain Events.
 *
 * Implementations (all live in Infrastructure):
 *   - InMemorySyncDomainEventBus — invokes listeners in the same process,
 *     synchronously.
 *   - OutboxDomainEventBus       — persists in the outbox table; an
 *     independent worker delivers asynchronously.
 *   - SpyDomainEventBus          — captures events without delivering
 *     (tests).
 *
 * The binding is decided in the ServiceProvider according to APP_ENV.
 *
 * Purity rules:
 *  - Zero imports of Illuminate\…, App\…, or any external layer.
 *  - Only DomainEvents and arrays of DomainEvents.
 *  - Does not know about listeners — listeners are registered separately
 *    in DomainEventSubscriptions, so a publish-only bus (e.g. an SQS
 *    adapter) does not need to know about subscriptions.
 */
interface DomainEventBus
{
    /**
     * Publish one or more events. What happens next depends on the
     * implementation: sync (invoke listeners now), outbox (persist in
     * table and return), spy (capture in memory).
     *
     * Variadic so callers can write `$bus->publish($event)` or
     * `$bus->publish(...$aggregate->pullDomainEvents())` without first
     * checking for empty arrays.
     */
    public function publish(DomainEvent ...$events): void;
}
