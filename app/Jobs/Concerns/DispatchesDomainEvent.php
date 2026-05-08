<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventSerializer;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Shared body for the per-policy bucket jobs that drain the domain event
 * outbox. Each bucket (Default, Heavy, Critical) declares its own retry /
 * timeout / queue policy and delegates execution to this trait.
 *
 * Filters listeners by the bucket's policy: a listener whose declared
 * POLICY does not match the bucket's policy is skipped, so a single
 * outbox entry can be safely dispatched to multiple buckets when its
 * listeners use mixed policies.
 */
trait DispatchesDomainEvent
{
    public int $outboxEntryId;

    /**
     * Bucket policy name ('default'|'heavy'|'critical'). Bucket jobs
     * implement this to filter listeners during processing.
     */
    abstract public function policy(): string;

    public function runDispatch(
        OutboxRepository $outbox,
        DomainEventSerializer $serializer,
        DomainEventSubscriptions $subscriptions,
        Container $container,
    ): void {
        $entry = $outbox->findById($this->outboxEntryId);
        if ($entry === null) {
            return;
        }

        if ($entry->completedAt !== null) {
            return;
        }

        $event = $serializer->deserialize($entry->eventName, $entry->payload);

        $bucketPolicy = $this->policy();

        foreach ($subscriptions->listenersFor($event) as $listenerClass) {
            $listenerPolicy = defined("{$listenerClass}::POLICY") ? $listenerClass::POLICY : 'default';

            if ($listenerPolicy !== $bucketPolicy) {
                continue;
            }

            $listener = $container->get($listenerClass);
            $listener($event);
        }

        $outbox->markCompleted($entry->id);
    }

    public function failedDispatch(Throwable $exception): void
    {
        app(OutboxRepository::class)
            ->markFailed($this->outboxEntryId, $exception->getMessage());
    }
}
