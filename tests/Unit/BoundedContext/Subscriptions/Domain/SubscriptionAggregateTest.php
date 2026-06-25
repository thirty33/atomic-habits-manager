<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Subscriptions\Domain;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\PaymentWasNotifiedForSubscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionReturnedToActive;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionWasUpgraded;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionTransitionNotAllowed;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

class SubscriptionAggregateTest extends TestCase
{
    private function activeFree(): Subscription
    {
        $subscription = Subscription::startFree(UserId::from(1));
        $subscription->assignId(SubscriptionId::from(5));

        return $subscription;
    }

    public function test_start_free_is_active_on_the_free_tier(): void
    {
        $subscription = Subscription::startFree(UserId::from(1));

        $this->assertTrue($subscription->planTier()->isFree());
        $this->assertTrue($subscription->status()->isActive());
    }

    public function test_mark_payment_notified_guards_and_records_event(): void
    {
        $subscription = $this->activeFree();

        $subscription->markPaymentNotified();
        $subscription->recordEventsAfterAssign();

        $this->assertSame(SubscriptionStatus::PAYMENT_NOTIFIED, $subscription->status()->value());

        $events = $subscription->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentWasNotifiedForSubscription::class, $events[0]);
    }

    public function test_mark_payment_notified_twice_is_not_allowed(): void
    {
        $subscription = $this->activeFree();
        $subscription->markPaymentNotified();

        $this->expectException(SubscriptionTransitionNotAllowed::class);
        $subscription->markPaymentNotified();
    }

    public function test_upgrade_changes_tier_and_returns_to_active_recording_event(): void
    {
        $subscription = $this->activeFree();
        $subscription->markPaymentNotified();

        $subscription->upgradeTo(PlanTier::unlimited());
        $subscription->recordEventsAfterAssign();

        $this->assertTrue($subscription->planTier()->isUnlimited());
        $this->assertTrue($subscription->status()->isActive());

        $events = $subscription->pullDomainEvents();
        $upgrades = array_values(array_filter($events, static fn ($e): bool => $e instanceof SubscriptionWasUpgraded));
        $this->assertCount(1, $upgrades);
        $this->assertSame('free', $upgrades[0]->fromTier);
        $this->assertSame('unlimited', $upgrades[0]->toTier);
    }

    public function test_return_to_active_keeps_the_tier_and_records_event(): void
    {
        $subscription = $this->activeFree();
        $subscription->markPaymentNotified();
        $subscription->recordEventsAfterAssign();
        $subscription->pullDomainEvents();

        $subscription->returnToActive();
        $subscription->recordEventsAfterAssign();

        $this->assertTrue($subscription->planTier()->isFree());
        $this->assertTrue($subscription->status()->isActive());

        $events = $subscription->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SubscriptionReturnedToActive::class, $events[0]);
    }

    public function test_return_to_active_from_active_is_not_allowed(): void
    {
        $subscription = $this->activeFree();

        $this->expectException(SubscriptionTransitionNotAllowed::class);
        $subscription->returnToActive();
    }
}
