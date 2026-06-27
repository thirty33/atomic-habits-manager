<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Subscriptions\Domain;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanLimits;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanModules;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanPolicy;
use PHPUnit\Framework\TestCase;

class PlanPolicyTest extends TestCase
{
    public function test_free_tier_caps_habits_at_three(): void
    {
        $limits = new PlanLimits;

        $this->assertSame(3, $limits->maxHabits(PlanTier::free()));
        $this->assertTrue($limits->canCreateHabit(PlanTier::free(), 2));
        $this->assertFalse($limits->canCreateHabit(PlanTier::free(), 3));
        $this->assertFalse($limits->canCreateHabit(PlanTier::free(), 4));
    }

    public function test_unlimited_tier_has_no_habit_cap(): void
    {
        $limits = new PlanLimits;

        $this->assertNull($limits->maxHabits(PlanTier::unlimited()));
        $this->assertTrue($limits->canCreateHabit(PlanTier::unlimited(), 999));
    }

    public function test_free_tier_modules_exclude_atomic_ia(): void
    {
        $modules = new PlanModules;

        $this->assertSame(['habits', 'calendar', 'reports'], $modules->modulesFor(PlanTier::free()));
        $this->assertTrue($modules->allows(PlanTier::free(), 'reports'));
        $this->assertFalse($modules->allows(PlanTier::free(), 'atomic_ia'));
    }

    public function test_unlimited_tier_modules_include_atomic_ia(): void
    {
        $modules = new PlanModules;

        $this->assertContains('atomic_ia', $modules->modulesFor(PlanTier::unlimited()));
        $this->assertTrue($modules->allows(PlanTier::unlimited(), 'atomic_ia'));
    }

    public function test_policy_facade_delegates_to_limits_and_modules(): void
    {
        $policy = new PlanPolicy;

        $this->assertSame(3, $policy->maxHabits(PlanTier::free()));
        $this->assertNull($policy->maxHabits(PlanTier::unlimited()));
        $this->assertFalse($policy->allowsModule(PlanTier::free(), 'atomic_ia'));
        $this->assertTrue($policy->allowsModule(PlanTier::unlimited(), 'atomic_ia'));
        $this->assertFalse($policy->canCreateHabit(PlanTier::free(), 3));
    }
}
