<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\NotifyPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.binance.payment_email' => 'deposit@binance.com']);

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::free(),
            amount: Amount::from(0.0),
            currency: Currency::from('USDT'),
        ));

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::unlimited(),
            amount: Amount::from(5.0),
            currency: Currency::from('USDT'),
        ));
    }

    public function test_plans_json_returns_both_plans_binance_email_and_current_tier(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('subscriptions.plans.json'))
            ->assertOk();

        $response->assertJsonStructure([
            'plans' => [
                ['tier', 'amount', 'currency', 'modules', 'max_habits'],
            ],
            'binance_payment_email',
            'current_tier',
        ]);

        $this->assertCount(2, $response->json('plans'));
        $response->assertJsonPath('plans.0.tier', PlanTier::FREE);
        $response->assertJsonPath('plans.1.tier', PlanTier::UNLIMITED);
        $response->assertJsonPath('binance_payment_email', 'deposit@binance.com');
        $response->assertJsonPath('current_tier', PlanTier::FREE);
    }

    public function test_notify_payment_records_a_notified_payment_and_returns_a_success_toast(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'payer@binance.com',
                'tx_reference' => '0xabc',
            ])
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertNotNull($response->json('extra.payment_id'));

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->user_id,
            'plan' => PlanTier::UNLIMITED,
            'payer_binance_email' => 'payer@binance.com',
            'status' => PaymentStatus::PAYMENT_NOTIFIED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->user_id,
            'status' => SubscriptionStatus::PAYMENT_NOTIFIED,
        ]);
    }

    public function test_notify_payment_twice_is_rejected_with_422_not_500(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'payer@binance.com',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'payer@binance.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan_tier');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_notify_payment_rejects_an_invalid_binance_email_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'not-an-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payer_binance_email');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_notify_payment_requires_authentication(): void
    {
        $this->postJson(route('subscriptions.notify-payment'), [
            'payer_binance_email' => 'payer@binance.com',
        ])->assertStatus(401);
    }

    public function test_register_claims_the_guest_account_and_sends_verification_email(): void
    {
        Notification::fake();

        $guest = User::factory()->guest()->create([
            'name' => 'Guest',
        ]);

        $this->actingAs($guest)
            ->postJson(route('subscriptions.register'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertDatabaseHas('users', [
            'user_id' => $guest->user_id,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);

        Notification::assertSentTo($guest->fresh(), VerifyEmail::class);
    }

    public function test_register_rejects_a_duplicate_email_with_422_on_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->postJson(route('subscriptions.register'), [
                'name' => 'Jane Doe',
                'email' => 'taken@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_on_an_already_claimed_account_is_rejected_without_mutating_it(): void
    {
        $claimed = User::factory()->create([
            'name' => 'Already Real',
            'email' => 'already@example.com',
        ]);

        $this->actingAs($claimed)
            ->postJson(route('subscriptions.register'), [
                'name' => 'Overwrite Attempt',
                'email' => 'overwrite@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('users', [
            'user_id' => $claimed->user_id,
            'name' => 'Already Real',
            'email' => 'already@example.com',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'overwrite@example.com']);
    }

    public function test_notify_payment_for_the_free_tier_is_rejected_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'payer@binance.com',
                'plan_tier' => PlanTier::FREE,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'plan_tier' => __('El plan seleccionado no es válido.'),
            ]);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $user->user_id,
            'status' => SubscriptionStatus::PAYMENT_NOTIFIED,
        ]);
    }

    public function test_notify_payment_when_the_unlimited_plan_is_absent_returns_422_not_500(): void
    {
        \App\Models\Plan::query()->where('tier', PlanTier::UNLIMITED)->delete();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscriptions.notify-payment'), [
                'payer_binance_email' => 'payer@binance.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan_tier');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_confirm_payment_upgrades_the_user_to_unlimited(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());
        $user = User::factory()->create();

        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        $this->actingAs($admin)
            ->putJson(route('backoffice.users.confirm-payment', $user->user_id))
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $subscription = SubscriptionModel::query()->where('user_id', $user->user_id)->firstOrFail();
        $this->assertSame(PlanTier::UNLIMITED, $subscription->plan_tier);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_non_admin_cannot_confirm_payment(): void
    {
        $nonAdmin = User::factory()->create();
        $user = User::factory()->create();

        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        $this->actingAs($nonAdmin)
            ->putJson(route('backoffice.users.confirm-payment', $user->user_id))
            ->assertForbidden();
    }

    public function test_admin_reject_payment_returns_the_user_to_active(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());
        $user = User::factory()->create();

        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        $this->actingAs($admin)
            ->putJson(route('backoffice.users.reject-payment', $user->user_id))
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->user_id,
            'plan_tier' => PlanTier::FREE,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->user_id,
            'status' => PaymentStatus::REJECTED,
        ]);
    }

    public function test_non_admin_cannot_reject_payment(): void
    {
        $nonAdmin = User::factory()->create();
        $user = User::factory()->create();

        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $user->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        $this->actingAs($nonAdmin)
            ->putJson(route('backoffice.users.reject-payment', $user->user_id))
            ->assertForbidden();
    }
}
