<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Identity;

use App\Models\User;
use Core\BoundedContext\Identity\Application\Actions\ActivateUser;
use Core\BoundedContext\Identity\Application\Actions\ClaimGuestAccount;
use Core\BoundedContext\Identity\Application\Actions\DeactivateUser;
use Core\BoundedContext\Identity\Application\Actions\RegisterGuestUser;
use Core\BoundedContext\Identity\Application\DTOs\ClaimGuestAccountData;
use Core\BoundedContext\Identity\Domain\Exceptions\EmailAlreadyTaken;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestUserFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_guest_creates_active_user_and_free_subscription(): void
    {
        $response = app(RegisterGuestUser::class)();

        $this->assertTrue($response->isActive);

        $this->assertDatabaseHas('users', [
            'user_id' => $response->userId,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $response->userId,
            'plan_tier' => PlanTier::FREE,
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    public function test_claim_fills_guest_identity_over_the_same_user_id(): void
    {
        $guest = app(RegisterGuestUser::class)();

        $claimed = app(ClaimGuestAccount::class)(ClaimGuestAccountData::fromArray([
            'user_id' => $guest->userId,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'supersecret',
        ]));

        $this->assertSame($guest->userId, $claimed->userId);
        $this->assertSame('jane@example.com', $claimed->email);

        $this->assertDatabaseHas('users', [
            'user_id' => $guest->userId,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_claim_rejects_email_already_used_by_another_user(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $guest = app(RegisterGuestUser::class)();

        $this->expectException(EmailAlreadyTaken::class);

        app(ClaimGuestAccount::class)(ClaimGuestAccountData::fromArray([
            'user_id' => $guest->userId,
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'password' => 'supersecret',
        ]));
    }

    public function test_activate_and_deactivate_use_cases_persist_state(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        app(DeactivateUser::class)($user->user_id);
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'is_active' => false]);

        app(ActivateUser::class)($user->user_id);
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'is_active' => true]);
    }
}
