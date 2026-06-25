<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_passes_the_backoffice_guard(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->getJson(route('backoffice.habits.json'))
            ->assertOk();
    }

    public function test_user_deactivated_mid_session_is_blocked(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->getJson(route('backoffice.habits.json'))
            ->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user)
            ->getJson(route('backoffice.habits.json'))
            ->assertForbidden();
    }
}
