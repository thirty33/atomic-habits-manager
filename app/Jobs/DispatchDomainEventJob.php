<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\DispatchesDomainEvent;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventSerializer;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * @deprecated Replaced by per-policy bucket jobs:
 *   - {@see DispatchDomainEventDefaultJob}
 *   - {@see DispatchDomainEventHeavyJob}
 *   - {@see DispatchDomainEventCriticalJob}
 *
 * Kept on disk to absorb in-flight queue messages dispatched before the
 * Pattern 3 deploy. New code MUST NOT dispatch this job.
 *
 * Behaves as the 'default' bucket. Will be removed once all queues drain.
 */
final class DispatchDomainEventJob implements ShouldQueue
{
    use DispatchesDomainEvent;
    use Queueable;

    public int $tries = 5;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct(public int $outboxEntryId) {}

    public function policy(): string
    {
        return 'default';
    }

    public function handle(
        OutboxRepository $outbox,
        DomainEventSerializer $serializer,
        DomainEventSubscriptions $subscriptions,
        Container $container,
    ): void {
        $this->runDispatch($outbox, $serializer, $subscriptions, $container);
    }

    public function failed(Throwable $exception): void
    {
        $this->failedDispatch($exception);
    }
}
