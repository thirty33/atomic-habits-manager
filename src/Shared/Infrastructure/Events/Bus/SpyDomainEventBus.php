<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Bus;

use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Domain\Events\DomainEvent;

/**
 * Test bus: captures published events without invoking any listeners.
 * Allows tests to assert which events a Use Case emits, without running
 * a worker or registering listeners.
 *
 * Replacement for `Mockery::mock(DomainEventBus::class)` — more expressive
 * and without fragile expectations.
 */
final class SpyDomainEventBus implements DomainEventBus
{
    /** @var list<DomainEvent> */
    private array $captured = [];

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->captured[] = $event;
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function captured(): array
    {
        return $this->captured;
    }

    /**
     * @param  class-string<DomainEvent>  $eventClass
     * @return list<DomainEvent>
     */
    public function capturedOf(string $eventClass): array
    {
        return array_values(array_filter(
            $this->captured,
            static fn (DomainEvent $e) => $e instanceof $eventClass,
        ));
    }

    public function reset(): void
    {
        $this->captured = [];
    }
}
