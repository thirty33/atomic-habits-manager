<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Policy;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\Exceptions\HabitLimitReached;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanLimits;

/**
 * Domain policy guard the Habits BC calls before creating a habit. It receives
 * the current habit count (so it stays decoupled from the Habits repository)
 * and throws HabitLimitReached (a 422 ProvidesValidationErrors) when the tier
 * would exceed its cap. Unlimited always passes.
 */
final readonly class EnsureCanCreateHabit
{
    public function __construct(private PlanLimits $limits = new PlanLimits) {}

    /**
     * @throws HabitLimitReached
     */
    public function __invoke(PlanTier $tier, int $currentHabitCount): void
    {
        if ($this->limits->canCreateHabit($tier, $currentHabitCount)) {
            return;
        }

        throw HabitLimitReached::forTier($tier, (int) $this->limits->maxHabits($tier));
    }
}
