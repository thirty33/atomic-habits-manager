<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Policy;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Domain policy facade over PlanLimits + PlanModules. The "rules of a plan"
 * live entirely in this Policy folder; consumers (use cases, sidebar,
 * middleware) ask the policy instead of branching on the tier themselves.
 *
 * To change what a plan allows, edit PlanLimits/PlanModules — never the callers.
 */
final readonly class PlanPolicy
{
    public function __construct(
        private PlanLimits $limits = new PlanLimits,
        private PlanModules $modules = new PlanModules,
    ) {}

    public function maxHabits(PlanTier $tier): ?int
    {
        return $this->limits->maxHabits($tier);
    }

    public function canCreateHabit(PlanTier $tier, int $currentCount): bool
    {
        return $this->limits->canCreateHabit($tier, $currentCount);
    }

    /**
     * @return list<string>
     */
    public function modulesFor(PlanTier $tier): array
    {
        return $this->modules->modulesFor($tier);
    }

    public function allowsModule(PlanTier $tier, string $moduleCode): bool
    {
        return $this->modules->allows($tier, $moduleCode);
    }
}
