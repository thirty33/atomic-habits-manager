<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan;

use Core\BoundedContext\Subscriptions\Domain\Plan\Exceptions\PlanNotFound;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Write-side contract for plans. Bare DB operations only — the transaction
 * boundary lives in the Application use case (TransactionManager), never here.
 */
interface PlanRepository
{
    public function save(Plan $plan): void;

    /** @throws PlanNotFound */
    public function findByTier(PlanTier $tier): Plan;
}
