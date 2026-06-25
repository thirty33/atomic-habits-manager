<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\Events;

use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class SubscriptionWasUpgraded extends DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly int $userId,
        public readonly string $fromTier,
        public readonly string $toTier,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(SubscriptionId $subscriptionId, int $userId, string $fromTier, string $toTier): self
    {
        return new self(subscriptionId: $subscriptionId, userId: $userId, fromTier: $fromTier, toTier: $toTier);
    }

    public static function eventName(): string
    {
        return 'subscriptions.subscription_was_upgraded';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'subscription_id' => $this->subscriptionId->value(),
            'user_id' => $this->userId,
            'from_tier' => $this->fromTier,
            'to_tier' => $this->toTier,
        ];
    }

    /**
     * @param  array{subscription_id: int, user_id: int, from_tier: string, to_tier: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            subscriptionId: SubscriptionId::from((int) $primitives['subscription_id']),
            userId: (int) $primitives['user_id'],
            fromTier: (string) $primitives['from_tier'],
            toTier: (string) $primitives['to_tier'],
        );
    }
}
