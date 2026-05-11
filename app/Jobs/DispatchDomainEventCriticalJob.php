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
 * Critical bucket: effects that must NOT be lost (legal notifications,
 * payments, immutable audit). Aggressive retries on the shared SQS
 * queue — only the tries / timeout / backoff differ from the other
 * buckets.
 */
final class DispatchDomainEventCriticalJob implements ShouldQueue
{
    use DispatchesDomainEvent;
    use Queueable;

    public int $tries = 10;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(public int $outboxEntryId) {}

    public function policy(): string
    {
        return 'critical';
    }

    public function handle(
        OutboxRepository $outbox,
        DomainEventSerializer $serializer,
        DomainEventSubscriptions $subscriptions,
        Container $container,
    ): void {
        $this->runDispatch($outbox, $serializer, $subscriptions, $container);
    }

    public function failed(Throwable $e): void
    {
        $this->failedDispatch($e);
    }
}
