<?php

declare(strict_types=1);

namespace Tests\Feature\DomainEvents;

use App\Jobs\DispatchDomainEventHeavyJob;
use Core\Shared\Domain\Events\DomainEvent;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Core\Shared\Infrastructure\Events\Outbox\OutboxRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DispatchDomainEventBucketJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(BucketTestEvent::EVENT_NAME, BucketTestEvent::class);
    }

    public function test_heavy_bucket_invokes_listener_with_matching_policy_and_marks_completed(): void
    {
        BucketHeavyListener::reset();
        BucketDefaultListener::reset();

        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(BucketTestEvent::class, BucketHeavyListener::class);
        $subscriptions->register(BucketTestEvent::class, BucketDefaultListener::class);

        $entryId = $this->insertOutboxEntry();

        $this->app->make(DispatchDomainEventHeavyJob::class, ['outboxEntryId' => $entryId])
            ->handle(
                $this->app->make(OutboxRepository::class),
                $this->app->make(\Core\Shared\Infrastructure\Events\Outbox\DomainEventSerializer::class),
                $subscriptions,
                $this->app,
            );

        $this->assertSame(1, BucketHeavyListener::$invocations, 'Heavy listener should be invoked once');
        $this->assertSame(0, BucketDefaultListener::$invocations, 'Default listener should NOT run inside the heavy bucket');

        $entry = $this->app->make(OutboxRepository::class)->findById($entryId);
        $this->assertNotNull($entry?->completedAt);
    }

    public function test_bucket_job_is_idempotent_when_entry_already_completed(): void
    {
        BucketHeavyListener::reset();

        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(BucketTestEvent::class, BucketHeavyListener::class);

        $entryId = $this->insertOutboxEntry();

        $repository = $this->app->make(OutboxRepository::class);
        $repository->markCompleted($entryId);

        $this->app->make(DispatchDomainEventHeavyJob::class, ['outboxEntryId' => $entryId])
            ->handle(
                $repository,
                $this->app->make(\Core\Shared\Infrastructure\Events\Outbox\DomainEventSerializer::class),
                $subscriptions,
                $this->app,
            );

        $this->assertSame(0, BucketHeavyListener::$invocations, 'Listener must not run for already-completed entries');
    }

    private function insertOutboxEntry(): int
    {
        return (int) DB::table('domain_event_outbox')->insertGetId([
            'event_id' => bin2hex(random_bytes(16)),
            'event_name' => BucketTestEvent::EVENT_NAME,
            'payload' => json_encode(['marker' => 'bucket-test'], JSON_THROW_ON_ERROR),
            'occurred_on' => (new DateTimeImmutable)->format('Y-m-d H:i:s'),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

final class BucketTestEvent extends DomainEvent
{
    public const EVENT_NAME = 'tests.bucket_test_event';

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

final class BucketHeavyListener
{
    public const POLICY = 'heavy';

    public static int $invocations = 0;

    public static function reset(): void
    {
        self::$invocations = 0;
    }

    public function __invoke(BucketTestEvent $event): void
    {
        self::$invocations++;
    }
}

final class BucketDefaultListener
{
    public static int $invocations = 0;

    public static function reset(): void
    {
        self::$invocations = 0;
    }

    public function __invoke(BucketTestEvent $event): void
    {
        self::$invocations++;
    }
}
