<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Subscriptions\Application;

use Core\BoundedContext\Subscriptions\Application\Policy\EnsureCanCreateHabit;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\Exceptions\HabitLimitReached;
use Core\Shared\Domain\ProvidesValidationErrors;
use PHPUnit\Framework\TestCase;

class EnsureCanCreateHabitTest extends TestCase
{
    public function test_free_tier_allows_creation_under_the_cap(): void
    {
        $ensure = new EnsureCanCreateHabit;

        $ensure(PlanTier::free(), 0);
        $ensure(PlanTier::free(), 2);

        $this->expectNotToPerformAssertions();
    }

    public function test_free_tier_throws_422_at_the_cap(): void
    {
        $ensure = new EnsureCanCreateHabit;

        try {
            $ensure(PlanTier::free(), 3);
            $this->fail('Expected HabitLimitReached.');
        } catch (HabitLimitReached $e) {
            $this->assertInstanceOf(ProvidesValidationErrors::class, $e);
            $this->assertArrayHasKey('name', $e->validationErrors());
        }
    }

    public function test_free_tier_throws_over_the_cap(): void
    {
        $ensure = new EnsureCanCreateHabit;

        $this->expectException(HabitLimitReached::class);
        $ensure(PlanTier::free(), 4);
    }

    public function test_unlimited_tier_always_allows(): void
    {
        $ensure = new EnsureCanCreateHabit;

        $ensure(PlanTier::unlimited(), 0);
        $ensure(PlanTier::unlimited(), 1000);

        $this->expectNotToPerformAssertions();
    }
}
