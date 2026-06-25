<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Subscriptions;

use App\Models\User;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\NotifyPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Application\Subscription\Actions\StartFreeSubscription;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionAlreadyExists;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #6 — the "one current subscription per user" invariant. The idempotent ensure
 * use case (StartFreeSubscription) never creates a second one, and creating a
 * brand-new subscription for a user who already has one is rejected in the
 * domain (SubscriptionAlreadyExists), not silently or with a 500.
 */
class SubscriptionInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_free_subscription_is_idempotent_and_returns_the_existing_subscription(): void
    {
        $user = User::factory()->create();
        $ensure = app(StartFreeSubscription::class);

        $first = $ensure($user->user_id);
        $second = $ensure($user->user_id);

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_repository_exists_for_user_reflects_the_invariant(): void
    {
        $user = User::factory()->create();
        $repository = app(SubscriptionRepository::class);
        $id = UserId::from($user->user_id);

        $this->assertFalse($repository->existsForUser($id));

        app(StartFreeSubscription::class)($user->user_id);

        $this->assertTrue($repository->existsForUser($id));
    }

    public function test_creating_a_second_subscription_for_a_user_is_rejected_in_the_domain(): void
    {
        $user = User::factory()->create();
        $repository = app(SubscriptionRepository::class);

        app(StartFreeSubscription::class)($user->user_id);

        $duplicate = Subscription::startFree(UserId::from($user->user_id));

        $this->expectException(SubscriptionAlreadyExists::class);

        $repository->save($duplicate);
    }

    public function test_notify_payment_does_not_create_a_second_subscription(): void
    {
        $user = User::factory()->create();

        app(StartFreeSubscription::class)($user->user_id);

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::unlimited(),
            amount: Amount::from(5.0),
            currency: Currency::from('USDT'),
        ));

        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        $this->assertDatabaseCount('subscriptions', 1);
    }
}
