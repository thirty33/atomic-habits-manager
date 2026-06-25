<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\NotifyPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Application\Subscription\Actions\StartFreeSubscription;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_endpoint_deactivates_a_user_and_persists(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->putJson(route('backoffice.users.activation', $target->user_id), ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertDatabaseHas('users', [
            'user_id' => $target->user_id,
            'is_active' => false,
        ]);
    }

    public function test_activation_endpoint_activates_a_user_and_persists(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());
        $target = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->putJson(route('backoffice.users.activation', $target->user_id), ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertDatabaseHas('users', [
            'user_id' => $target->user_id,
            'is_active' => true,
        ]);
    }

    public function test_users_json_returns_the_datatable_shape(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());
        User::factory()->count(2)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('backoffice.users.json'))
            ->assertOk();

        $response->assertJsonStructure([
            'title',
            'text_model',
            'table_columns',
            'table_data' => ['data', 'pagination'],
            'table_buttons',
            'modals',
            'filter_fields',
        ]);

        $response->assertJsonPath('table_data.data.0.pk_name', 'user_id');
        $response->assertJsonPath('table_data.data.0.plan_tier', 'free');
    }

    public function test_users_json_composes_tier_and_notified_payment_per_row_via_batched_reads(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create());

        app(PlanRepository::class)->save(Plan::create(
            tier: PlanTier::unlimited(),
            amount: Amount::from(5.0),
            currency: Currency::from('USDT'),
        ));

        $free = User::factory()->create(['name' => 'Free User']);
        app(StartFreeSubscription::class)($free->user_id);

        $notified = User::factory()->create(['name' => 'Notified User']);
        app(NotifyPayment::class)(new NotifyPaymentData(
            userId: $notified->user_id,
            planTier: PlanTier::UNLIMITED,
            payerBinanceEmail: 'payer@binance.com',
        ));

        DB::enableQueryLog();

        $response = $this->actingAs($admin)
            ->getJson(route('backoffice.users.json').'?sorter[column]=name&sorter[direction]=asc')
            ->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $rows = collect($response->json('table_data.data'))->keyBy('name');

        $this->assertSame(PlanTier::FREE, $rows['Free User']['plan_tier']);
        $this->assertFalse($rows['Free User']['has_notified_payment']);

        $this->assertSame(PlanTier::FREE, $rows['Notified User']['plan_tier']);
        $this->assertTrue($rows['Notified User']['has_notified_payment']);

        $tierQueries = collect($queries)->filter(
            static fn (array $q): bool => str_contains($q['query'], 'plan_tier')
                && str_contains($q['query'], 'in (')
        );
        $paymentQueries = collect($queries)->filter(
            static fn (array $q): bool => str_contains($q['query'], 'from `payments`')
                && str_contains($q['query'], 'in (')
        );

        $this->assertLessThanOrEqual(1, $tierQueries->count(), 'tiersOf must run at most one batched query per page.');
        $this->assertLessThanOrEqual(1, $paymentQueries->count(), 'usersWithNotifiedPayment must run at most one batched query per page.');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $regular = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($regular)
            ->getJson(route('backoffice.users.json'))
            ->assertForbidden();

        $this->actingAs($regular)
            ->get(route('backoffice.users.index'))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_toggle_activation_of_another_user(): void
    {
        $regular = User::factory()->create();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($regular)
            ->putJson(route('backoffice.users.activation', $target->user_id), ['is_active' => false])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'user_id' => $target->user_id,
            'is_active' => true,
        ]);
    }
}
