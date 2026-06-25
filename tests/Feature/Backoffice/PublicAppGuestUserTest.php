<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6b — public app (D1 guest auto-user) and non-gating email verification
 * (D4). The app surface must be reachable without a login wall and without
 * confirming the email.
 */
class PublicAppGuestUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_unauthenticated_visit_to_the_app_entry_creates_an_active_guest_with_a_free_subscription(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->get(route('backoffice.dashboard.index'))->assertOk();

        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);

        $guest = User::query()->firstOrFail();
        $this->assertTrue($guest->is_active);
        $this->assertTrue($guest->isGuest());

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $guest->user_id,
            'plan_tier' => PlanTier::FREE,
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    public function test_app_navigation_reuses_the_same_guest_within_a_session(): void
    {
        $this->get(route('backoffice.dashboard.index'))->assertOk();
        $this->get(route('backoffice.habits.index'))->assertOk();
        $this->get(route('backoffice.calendar.index'))->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_unverified_free_user_can_reach_habits_calendar_and_reports_without_a_verify_email_redirect(): void
    {
        $user = User::factory()->unverified()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('backoffice.habits.index'))->assertOk();
        $this->actingAs($user)->get(route('backoffice.calendar.index'))->assertOk();
        $this->actingAs($user)->get(route('backoffice.daily-reports.index'))->assertOk();
    }

    public function test_admin_login_still_routes_to_the_backoffice_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@admin.com',
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame($admin->user_id, auth()->id());
        $response->assertRedirect(route('backoffice.dashboard.index'));
    }

    public function test_logged_in_real_user_is_never_replaced_by_a_guest(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('backoffice.dashboard.index'))->assertOk();

        $this->assertSame($user->user_id, auth()->id());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_plans_json_reports_is_guest_false_for_a_registered_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('subscriptions.plans.json'))
            ->assertOk()
            ->assertJsonPath('is_guest', false)
            ->assertJsonPath('registered', true);
    }

    public function test_plans_json_reports_is_guest_true_for_an_unclaimed_guest(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->getJson(route('subscriptions.plans.json'))
            ->assertOk()
            ->assertJsonPath('is_guest', true)
            ->assertJsonPath('registered', false);
    }

    public function test_a_guest_can_still_reach_the_login_page_to_claim_or_sign_in(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)->get(route('login'))->assertOk();
        $this->actingAs($guest)->get(route('register'))->assertOk();
    }

    public function test_a_real_user_whose_email_ends_in_guest_local_is_not_treated_as_a_guest(): void
    {
        $user = User::factory()->create([
            'email' => 'real_'.bin2hex(random_bytes(4)).'@guest.local',
            'claimed_at' => now(),
        ]);

        $this->assertFalse($user->isGuest());

        $this->actingAs($user)
            ->getJson(route('subscriptions.plans.json'))
            ->assertOk()
            ->assertJsonPath('is_guest', false)
            ->assertJsonPath('registered', true);
    }

    public function test_a_registered_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_write_requests_do_not_silently_create_a_guest(): void
    {
        $this->postJson(route('backoffice.habits.store'), [])->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_seeder_marks_the_admin_email_as_verified(): void
    {
        $this->seed(\Database\Seeders\AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertNotNull($admin->email_verified_at);

        SubscriptionModel::query()->where('user_id', $admin->user_id)->get();
    }
}
