<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DispatchDomainEventCriticalJob;
use App\Jobs\DispatchDomainEventDefaultJob;
use App\Jobs\DispatchDomainEventHeavyJob;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventSerializer;
use Core\Shared\Infrastructure\Events\Outbox\OutboxEntryDto;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;
use Illuminate\Console\Command;

final class RelayDomainEventsCommand extends Command
{
    protected $signature = 'events:relay
        {--once : Process only one batch and exit}
        {--limit=100 : Maximum events to process per run}
        {--redispatch-failed : Reopen failed entries for redispatch}';

    protected $description = 'Drain the domain event outbox by dispatching pending events to per-policy bucket jobs.';

    public function __construct(
        private OutboxRepository $outbox,
        private DomainEventSerializer $serializer,
        private DomainEventSubscriptions $subscriptions,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ((bool) $this->option('redispatch-failed')) {
            return $this->redispatchFailed((int) $this->option('limit'));
        }

        $limit = (int) $this->option('limit');
        $once = (bool) $this->option('once');

        $processed = 0;

        do {
            $entries = $this->outbox->pending($limit);
            if ($entries === []) {
                break;
            }

            foreach ($entries as $entry) {
                $this->dispatchEntry($entry);
                $this->outbox->markDispatched($entry->id);
                $processed++;
            }
        } while (! $once);

        $this->components->info("Dispatched {$processed} domain event(s).");

        return self::SUCCESS;
    }

    private function dispatchEntry(OutboxEntryDto $entry): void
    {
        $event = $this->serializer->deserialize($entry->eventName, $entry->payload);

        $bucketsDispatched = [];

        foreach ($this->subscriptions->listenersFor($event) as $listenerClass) {
            $jobClass = $this->bucketFor($listenerClass);

            if (isset($bucketsDispatched[$jobClass])) {
                continue;
            }

            $jobClass::dispatch($entry->id);
            $bucketsDispatched[$jobClass] = true;
        }
    }

    /**
     * @param  class-string  $listenerClass
     * @return class-string
     */
    private function bucketFor(string $listenerClass): string
    {
        $policy = defined("{$listenerClass}::POLICY") ? $listenerClass::POLICY : 'default';

        return match ($policy) {
            'heavy' => DispatchDomainEventHeavyJob::class,
            'critical' => DispatchDomainEventCriticalJob::class,
            default => DispatchDomainEventDefaultJob::class,
        };
    }

    private function redispatchFailed(int $limit): int
    {
        $entries = $this->outbox->listFailed($limit);

        foreach ($entries as $entry) {
            $this->outbox->resetForRedispatch($entry->id);
        }

        $count = count($entries);
        $this->components->info("Reopened {$count} failed entry(ies) for redispatch.");

        return self::SUCCESS;
    }
}
