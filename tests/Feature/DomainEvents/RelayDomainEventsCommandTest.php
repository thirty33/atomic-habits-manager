<?php

declare(strict_types=1);

namespace Tests\Feature\DomainEvents;

use App\Jobs\DispatchDomainEventCriticalJob;
use App\Jobs\DispatchDomainEventDefaultJob;
use App\Jobs\DispatchDomainEventHeavyJob;
use Core\Shared\Domain\Events\DomainEvent;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class RelayDomainEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(RelayTestEvent::EVENT_NAME, RelayTestEvent::class);
    }

    public function test_dispatches_heavy_bucket_when_listener_declares_heavy_policy(): void
    {
        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(RelayTestEvent::class, RelayHeavyListener::class);

        $entryId = $this->insertOutboxEntry();

        Queue::fake();

        $this->artisan('events:relay', ['--once' => true])->assertSuccessful();

        Queue::assertPushed(DispatchDomainEventHeavyJob::class, fn ($job) => $job->outboxEntryId === $entryId);
        Queue::assertNotPushed(DispatchDomainEventDefaultJob::class);
        Queue::assertNotPushed(DispatchDomainEventCriticalJob::class);

        $entry = $this->app->make(OutboxRepository::class)->findById($entryId);
        $this->assertNotNull($entry?->dispatchedAt);
    }

    public function test_defaults_to_default_bucket_when_listener_has_no_policy(): void
    {
        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(RelayTestEvent::class, RelayUnpoliticedListener::class);

        $entryId = $this->insertOutboxEntry();

        Queue::fake();

        $this->artisan('events:relay', ['--once' => true])->assertSuccessful();

        Queue::assertPushed(DispatchDomainEventDefaultJob::class, fn ($job) => $job->outboxEntryId === $entryId);
        Queue::assertNotPushed(DispatchDomainEventHeavyJob::class);
        Queue::assertNotPushed(DispatchDomainEventCriticalJob::class);
    }

    public function test_dispatches_critical_bucket_when_listener_declares_critical_policy(): void
    {
        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(RelayTestEvent::class, RelayCriticalListener::class);

        $entryId = $this->insertOutboxEntry();

        Queue::fake();

        $this->artisan('events:relay', ['--once' => true])->assertSuccessful();

        Queue::assertPushed(DispatchDomainEventCriticalJob::class, fn ($job) => $job->outboxEntryId === $entryId);
        Queue::assertNotPushed(DispatchDomainEventDefaultJob::class);
        Queue::assertNotPushed(DispatchDomainEventHeavyJob::class);
    }

    public function test_redispatch_failed_resets_failed_entries(): void
    {
        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(RelayTestEvent::class, RelayHeavyListener::class);

        $entryId = $this->insertOutboxEntry();
        $this->app->make(OutboxRepository::class)->markFailed($entryId, 'boom');

        $this->artisan('events:relay', ['--redispatch-failed' => true])->assertSuccessful();

        $entry = $this->app->make(OutboxRepository::class)->findById($entryId);
        $this->assertNull($entry?->failedAt);
        $this->assertNull($entry?->dispatchedAt);
        $this->assertNull($entry?->completedAt);
    }

    public function test_skips_already_failed_entries_in_pending_run(): void
    {
        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(RelayTestEvent::class, RelayHeavyListener::class);

        $entryId = $this->insertOutboxEntry();
        $this->app->make(OutboxRepository::class)->markFailed($entryId, 'boom');

        Queue::fake();

        $this->artisan('events:relay', ['--once' => true])->assertSuccessful();

        Queue::assertNothingPushed();
    }

    private function insertOutboxEntry(): int
    {
        $payload = json_encode(['marker' => 'relay-test'], JSON_THROW_ON_ERROR);

        return (int) DB::table('domain_event_outbox')->insertGetId([
            'event_id' => bin2hex(random_bytes(16)),
            'event_name' => RelayTestEvent::EVENT_NAME,
            'payload' => $payload,
            'occurred_on' => (new DateTimeImmutable)->format('Y-m-d H:i:s'),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

final class RelayTestEvent extends DomainEvent
{
    public const EVENT_NAME = 'tests.relay_test_event';

    public function __construct(
        public readonly string $marker,
        ?DateTimeImmutable $occurredOn = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredOn ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return self::EVENT_NAME;
    }

    public function toPrimitives(): array
    {
        return ['marker' => $this->marker];
    }

    /**
     * @param  array{marker: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives['marker']);
    }
}

final readonly class RelayHeavyListener
{
    public const POLICY = 'heavy';

    public function __invoke(RelayTestEvent $event): void
    {
        // no-op: the relay only dispatches the bucket job; the listener
        // itself is exercised by the bucket job tests, not here.
    }
}

final readonly class RelayUnpoliticedListener
{
    public function __invoke(RelayTestEvent $event): void
    {
        // no-op
    }
}

final readonly class RelayCriticalListener
{
    public const POLICY = 'critical';

    public function __invoke(RelayTestEvent $event): void
    {
        // no-op
    }
}
