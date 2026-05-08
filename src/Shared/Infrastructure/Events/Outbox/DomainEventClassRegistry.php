<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Core\Shared\Domain\Events\DomainEvent;

/**
 * Maps eventName ("habits.was_updated") to the concrete class.
 *
 * If a concrete class is renamed but the same eventName is preserved,
 * older events sitting in the outbox can still be processed correctly.
 */
final class DomainEventClassRegistry
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $map = [];

    /**
     * @param  class-string<DomainEvent>  $class
     */
    public function register(string $eventName, string $class): void
    {
        $this->map[$eventName] = $class;
    }

    /**
     * @return ?class-string<DomainEvent>
     */
    public function classFor(string $eventName): ?string
    {
        return $this->map[$eventName] ?? null;
    }

    /**
     * @return array<string, class-string<DomainEvent>>
     */
    public function all(): array
    {
        return $this->map;
    }
}
