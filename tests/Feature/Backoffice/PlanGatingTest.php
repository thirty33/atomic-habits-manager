<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\User;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 gating & enforcement: the free habit cap in CreateHabit, plan-aware
 * sidebar links, the module route middleware, and superadmin bypass.
 */
class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // (a) Free habit limit enforced in CreateHabit
    // -----------------------------------------------------------------------

    public function test_free_user_creating_a_fourth_habit_is_rejected_with_422(): void
    {
        $user = User::factory()->create();
        Habit::factory()->count(3)->create(['user_id' => $user->user_id]);

        $response = $this->actingAs($user)->postJson(route('backoffice.habits.store'), [
            'name' => 'Cuarto habito',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseMissing('habits', [
            'user_id' => $user->user_id,
            'name' => 'Cuarto habito',
        ]);
    }

    public function test_free_user_can_create_up_to_three_habits(): void
    {
        $user = User::factory()->create();
        Habit::factory()->count(2)->create(['user_id' => $user->user_id]);

        $this->actingAs($user)->postJson(route('backoffice.habits.store'), [
            'name' => 'Tercer habito',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ])->assertOk();

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->user_id,
            'name' => 'Tercer habito',
        ]);
    }

    // -----------------------------------------------------------------------
    // (b) Free user: no Atomic IA in sidebar + route blocked
    // -----------------------------------------------------------------------

    public function test_free_user_sidebar_omits_atomic_ia(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backoffice.habits.index'));

        $response->assertOk();
        $response->assertDontSee('Atomic IA');
    }

    public function test_free_user_is_blocked_from_atomic_ia_route_with_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('backoffice.atomic-ia.json'))
            ->assertStatus(403);
    }

    public function test_free_user_browser_navigation_to_atomic_ia_is_redirected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.atomic-ia.index'))
            ->assertRedirect(route('backoffice.habits.index'));
    }

    // -----------------------------------------------------------------------
    // (c) Unlimited user: >3 habits + Atomic IA visible and reachable
    // -----------------------------------------------------------------------

    public function test_unlimited_user_can_create_more_than_three_habits(): void
    {
        $user = $this->makeUnlimited(User::factory()->create());
        Habit::factory()->count(3)->create(['user_id' => $user->user_id]);

        $this->actingAs($user)->postJson(route('backoffice.habits.store'), [
            'name' => 'Cuarto sin limite',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ])->assertOk();

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->user_id,
            'name' => 'Cuarto sin limite',
        ]);
    }

    public function test_unlimited_user_sees_and_reaches_atomic_ia(): void
    {
        $user = $this->makeUnlimited(User::factory()->create());

        $page = $this->actingAs($user)->get(route('backoffice.habits.index'));
        $page->assertOk();
        $page->assertSee('Atomic IA');

        $this->actingAs($user)
            ->getJson(route('backoffice.atomic-ia.json'))
            ->assertOk();
    }

    // -----------------------------------------------------------------------
    // (d) Superadmin: sees every link and bypasses the plan gate
    // -----------------------------------------------------------------------

    public function test_superadmin_sees_all_links_and_bypasses_plan_gate(): void
    {
        $admin = $this->makeSuperadmin(User::factory()->create(['is_admin' => true]));

        $page = $this->actingAs($admin)->get(route('backoffice.habits.index'));
        $page->assertOk();
        $page->assertSee('Atomic IA');
        $page->assertSee('Usuarios');

        // Superadmin keeps a free tier yet bypasses the atomic_ia gate.
        $this->actingAs($admin)
            ->getJson(route('backoffice.atomic-ia.json'))
            ->assertOk();
    }

    // -----------------------------------------------------------------------
    // (e) tierOf defaults to free when there is no subscription row
    // -----------------------------------------------------------------------

    public function test_tier_of_defaults_to_free_without_a_subscription_row(): void
    {
        $user = User::factory()->create();

        $tier = $this->app->make(PlanCatalogReader::class)->tierOf(UserId::from($user->user_id));

        $this->assertTrue($tier->isFree());
        $this->assertSame(PlanTier::FREE, $tier->value());
        $this->assertDatabaseCount('subscriptions', 0);
    }
}
