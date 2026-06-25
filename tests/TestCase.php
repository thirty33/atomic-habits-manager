<?php

namespace Tests;

use App\Models\User;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Give a user the unlimited plan tier by persisting (or upgrading) their
     * current subscription. Used so plan-gated modules (e.g. atomic_ia) are
     * reachable in tests that are not exercising the gate itself.
     */
    protected function makeUnlimited(User $user): User
    {
        $repository = $this->app->make(SubscriptionRepository::class);
        $userId = UserId::from($user->user_id);

        $subscription = $repository->findByUser($userId);

        if ($subscription === null) {
            $subscription = Subscription::startFree($userId);
        }

        $subscription->upgradeTo(PlanTier::unlimited());
        $repository->save($subscription);

        return $user;
    }

    /**
     * Make a user a superadmin by seeding the Access catalog and assigning the
     * superadmin role (which holds the backoffice.admin capability).
     */
    protected function makeSuperadmin(User $user): User
    {
        $this->seed(AccessSeeder::class);

        $role = \App\Models\Role::query()
            ->where('name', AccessSeeder::SUPERADMIN_ROLE)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->role_id]);

        return $user;
    }
}
