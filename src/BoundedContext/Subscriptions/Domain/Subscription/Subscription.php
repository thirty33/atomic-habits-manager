<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\PaymentWasNotifiedForSubscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionReturnedToActive;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionWasUpgraded;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionTransitionNotAllowed;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Core\Shared\Domain\AggregateRoot;
use Core\Shared\Domain\Events\DomainEvent;
use LogicException;

/**
 * Aggregate Root for a user's CURRENT subscription: which plan tier they are on
 * and the status of that subscription. There is exactly one current
 * subscription per user (enforced by a unique user_id at persistence).
 *
 * A real state machine: every transition is guarded against the current status
 * and records a domain event. Because a transition may run on a brand-new
 * subscription (started free and not yet persisted), the events are staged and
 * flushed once an id is assigned — the repository calls
 * {@see self::recordEventsAfterAssign()} after persisting. Pure domain: no
 * Eloquent.
 */
final class Subscription extends AggregateRoot
{
    /** @var list<callable(SubscriptionId): DomainEvent> */
    private array $stagedEventFactories = [];

    private function __construct(
        private ?SubscriptionId $id,
        private UserId $userId,
        private PlanTier $planTier,
        private SubscriptionStatus $status,
    ) {}

    public static function startFree(UserId $userId): self
    {
        return new self(
            id: null,
            userId: $userId,
            planTier: PlanTier::free(),
            status: SubscriptionStatus::active(),
        );
    }

    public static function fromPrimitives(
        SubscriptionId $id,
        UserId $userId,
        PlanTier $planTier,
        SubscriptionStatus $status,
    ): self {
        return new self(id: $id, userId: $userId, planTier: $planTier, status: $status);
    }

    /**
     * The user reported a payment for an upgrade: keep the current tier but mark
     * the subscription as awaiting admin confirmation. Only an active
     * subscription may be moved to payment_notified.
     *
     * @throws SubscriptionTransitionNotAllowed
     */
    public function markPaymentNotified(): void
    {
        if (! $this->status->isActive()) {
            throw SubscriptionTransitionNotAllowed::from($this->status, 'notify a payment for');
        }

        $this->status = SubscriptionStatus::paymentNotified();

        $tier = $this->planTier->value();
        $userId = $this->userId->value();
        $this->stageEvent(static fn (SubscriptionId $id): DomainEvent => PaymentWasNotifiedForSubscription::now($id, $userId, $tier));
    }

    /**
     * The admin confirmed the payment: move to the given tier and back to active.
     */
    public function upgradeTo(PlanTier $tier): void
    {
        $fromTier = $this->planTier->value();

        $this->planTier = $tier;
        $this->status = SubscriptionStatus::active();

        $toTier = $tier->value();
        $userId = $this->userId->value();
        $this->stageEvent(static fn (SubscriptionId $id): DomainEvent => SubscriptionWasUpgraded::now($id, $userId, $fromTier, $toTier));
    }

    /**
     * The admin rejected the notified payment: return to the active state keeping
     * the current tier. Only a subscription awaiting confirmation may return.
     *
     * @throws SubscriptionTransitionNotAllowed
     */
    public function returnToActive(): void
    {
        if (! $this->status->isPaymentNotified()) {
            throw SubscriptionTransitionNotAllowed::from($this->status, 'return to active');
        }

        $this->status = SubscriptionStatus::active();

        $tier = $this->planTier->value();
        $userId = $this->userId->value();
        $this->stageEvent(static fn (SubscriptionId $id): DomainEvent => SubscriptionReturnedToActive::now($id, $userId, $tier));
    }

    /**
     * Flushes the events staged by transitions, now that the subscription has an
     * id. Called by the repository after persisting (mirrors the
     * record*AfterAssign pattern used by Payment/Permission).
     */
    public function recordEventsAfterAssign(): void
    {
        $id = $this->id();

        foreach ($this->stagedEventFactories as $factory) {
            $this->record($factory($id));
        }

        $this->stagedEventFactories = [];
    }

    public function assignId(SubscriptionId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Subscription already has an id.');
        }

        $this->id = $id;
    }

    public function id(): SubscriptionId
    {
        return $this->id ?? throw new LogicException('Subscription has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function planTier(): PlanTier
    {
        return $this->planTier;
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    /**
     * @param  callable(SubscriptionId): DomainEvent  $factory
     */
    private function stageEvent(callable $factory): void
    {
        $this->stagedEventFactories[] = $factory;
    }
}
