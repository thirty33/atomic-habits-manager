<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Subscriptions;

use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\ConfirmPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\NotifyPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\RejectPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\ConfirmPaymentData;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\RejectPaymentData;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\InvalidBinanceEmail;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentTierNotPayable;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\BoundedContext\Subscriptions\Domain\Plan\Exceptions\PlanNotFound;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::unlimited(),
            amount: Amount::from(5.0),
            currency: Currency::from('USDT'),
        ));
    }

    public function test_notify_payment_persists_and_flips_subscription_to_payment_notified(): void
    {
        $user = User::factory()->create();

        $paymentId = app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
            txReference: '0xabc123',
        ));

        $this->assertDatabaseHas('payments', [
            'payment_id' => $paymentId,
            'user_id' => $user->user_id,
            'plan' => PlanTier::UNLIMITED,
            'amount' => '5.00',
            'currency' => 'USDT',
            'payer_binance_email' => 'payer@binance.com',
            'tx_reference' => '0xabc123',
            'status' => PaymentStatus::PAYMENT_NOTIFIED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->user_id,
            'plan_tier' => PlanTier::FREE,
            'status' => SubscriptionStatus::PAYMENT_NOTIFIED,
        ]);
    }

    public function test_confirm_payment_marks_confirmed_and_upgrades_subscription_to_unlimited(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $paymentId = app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        app(ConfirmPayment::class)(new ConfirmPaymentData(
            paymentId: $paymentId,
            adminUserId: $admin->user_id,
        ));

        $this->assertDatabaseHas('payments', [
            'payment_id' => $paymentId,
            'status' => PaymentStatus::CONFIRMED,
            'confirmed_by' => $admin->user_id,
        ]);

        $subscription = SubscriptionModel::query()->where('user_id', $user->user_id)->firstOrFail();
        $this->assertSame(PlanTier::UNLIMITED, $subscription->plan_tier);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_reject_payment_marks_rejected_and_returns_subscription_to_active(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $paymentId = app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        app(RejectPayment::class)(new RejectPaymentData(
            paymentId: $paymentId,
            adminUserId: $admin->user_id,
        ));

        $this->assertDatabaseHas('payments', [
            'payment_id' => $paymentId,
            'status' => PaymentStatus::REJECTED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->user_id,
            'plan_tier' => PlanTier::FREE,
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    public function test_notify_payment_rejects_invalid_binance_email_as_validation_error(): void
    {
        $user = User::factory()->create();

        try {
            app(NotifyPayment::class)(new NotifyPaymentData(
                userId: $user->user_id,
                planTier: PlanTier::UNLIMITED,
                payerBinanceEmail: 'not-an-email',
            ));
            $this->fail('Expected InvalidBinanceEmail.');
        } catch (InvalidBinanceEmail $e) {
            $this->assertArrayHasKey('payer_binance_email', $e->validationErrors());
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_notify_payment_for_the_free_tier_is_rejected_and_records_nothing(): void
    {
        $user = User::factory()->create();

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::free(),
            amount: Amount::from(0.0),
            currency: Currency::from('USDT'),
        ));

        try {
            app(NotifyPayment::class)(new NotifyPaymentData(
                userId: $user->user_id,
                planTier: PlanTier::FREE,
                payerBinanceEmail: 'payer@binance.com',
            ));
            $this->fail('Expected PaymentTierNotPayable.');
        } catch (PaymentTierNotPayable $e) {
            $this->assertArrayHasKey('plan_tier', $e->validationErrors());
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $user->user_id,
            'status' => SubscriptionStatus::PAYMENT_NOTIFIED,
        ]);
    }

    public function test_notify_payment_when_the_plan_is_absent_surfaces_a_validation_error(): void
    {
        $missingPlanUser = User::factory()->create();

        // Drop the unlimited plan seeded in setUp so the tier has no catalog row.
        \App\Models\Plan::query()->where('tier', PlanTier::UNLIMITED)->delete();

        try {
            app(NotifyPayment::class)(new NotifyPaymentData(
                userId: $missingPlanUser->user_id,
                planTier: PlanTier::UNLIMITED,
                payerBinanceEmail: 'payer@binance.com',
            ));
            $this->fail('Expected PlanNotFound.');
        } catch (PlanNotFound $e) {
            $this->assertArrayHasKey('plan_tier', $e->validationErrors());
        }

        $this->assertDatabaseCount('payments', 0);
    }
}
