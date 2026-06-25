<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\Events;

use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class SubscriptionReturnedToActive extends DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly int $userId,
        public readonly string $planTier,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(SubscriptionId $subscriptionId, int $userId, string $planTier): self
    {
        return new self(subscriptionId: $subscriptionId, userId: $userId, planTier: $planTier);
    }

    public static function eventName(): string
    {
        return 'subscriptions.subscription_returned_to_active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'subscription_id' => $this->subscriptionId->value(),
            'user_id' => $this->userId,
            'plan_tier' => $this->planTier,
        ];
    }

    /**
     * @param  array{subscription_id: int, user_id: int, plan_tier: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            subscriptionId: SubscriptionId::from((int) $primitives['subscription_id']),
            userId: (int) $primitives['user_id'],
            planTier: (string) $primitives['plan_tier'],
        );
    }
}
