<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\Events;

use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class PaymentNotified extends DomainEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
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

    public static function now(PaymentId $paymentId, int $userId, string $planTier): self
    {
        return new self(paymentId: $paymentId, userId: $userId, planTier: $planTier);
    }

    public static function eventName(): string
    {
        return 'subscriptions.payment_notified';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'payment_id' => $this->paymentId->value(),
            'user_id' => $this->userId,
            'plan_tier' => $this->planTier,
        ];
    }

    /**
     * @param  array{payment_id: int, user_id: int, plan_tier: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            paymentId: PaymentId::from((int) $primitives['payment_id']),
            userId: (int) $primitives['user_id'],
            planTier: (string) $primitives['plan_tier'],
        );
    }
}
