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
 * Heavy bucket: long-running listeners (LLM, mass regeneration). Few
 * retries because each attempt is expensive.
 */
final class DispatchDomainEventHeavyJob implements ShouldQueue
{
    use DispatchesDomainEvent;
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 300;

    public function __construct(public int $outboxEntryId)
    {
        $this->onQueue('heavy');
    }

    public function policy(): string
    {
        return 'heavy';
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
