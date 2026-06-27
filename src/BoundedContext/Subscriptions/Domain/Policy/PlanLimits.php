<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Policy;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Domain policy: the quantitative limits each plan tier grants. This is the
 * single place where habit caps live — change a number here and the whole app
 * enforces the new limit, without touching controllers or middleware.
 *
 * Convention: maxHabits returns null for "no limit" (unlimited tier).
 */
final class PlanLimits
{
    private const MAX_HABITS = [
        PlanTier::FREE => 3,
        PlanTier::UNLIMITED => null,
    ];

    /**
     * Maximum number of habits a tier may own, or null when unbounded.
     */
    public function maxHabits(PlanTier $tier): ?int
    {
        return self::MAX_HABITS[$tier->value()] ?? null;
    }

    /**
     * Whether a tier may create one more habit given its current count.
     */
    public function canCreateHabit(PlanTier $tier, int $currentCount): bool
    {
        $max = $this->maxHabits($tier);

        return $max === null || $currentCount < $max;
    }
}
