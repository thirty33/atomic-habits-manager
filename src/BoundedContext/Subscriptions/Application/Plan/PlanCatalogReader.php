<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Plan;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Application-facing read port for the plan catalog (CQRS read side). Answers
 * "what plan is this user on?" and "what does a tier cost/allow?", composing the
 * user's subscription with the plan price catalog and the domain policy.
 *
 * Single read port for plans: it replaces the previously duplicated
 * Domain\Plan\PlanReader (existsForTier/priceOf) and Application\Plan\PlanReader
 * (tierOf/planInfo). Only the surface actually consumed is kept here (tierOf,
 * planInfo, existsForTier); the price lookup is an internal detail of the
 * implementation.
 */
interface PlanCatalogReader
{
    /**
     * The user's current tier. Defaults to free when the user has no
     * subscription row yet (guest/free-by-default).
     */
    public function tierOf(UserId $userId): PlanTier;

    /**
     * Bulk counterpart of {@see self::tierOf()} for a page of users: one query
     * instead of N. Returns the tier string keyed by user id; ids without a
     * subscription row are absent (the caller defaults them to free).
     *
     * @param  list<int>  $userIds
     * @return array<int, string> user_id => tier
     */
    public function tiersOf(array $userIds): array;

    /**
     * Full plan info for a tier: price + modules + habit limit.
     *
     * @return array{tier: string, amount: float, currency: string, modules: list<string>, max_habits: int|null}
     */
    public function planInfo(PlanTier $tier): array;

    /**
     * Whether the price catalog already has a plan for the given tier. Used to
     * make seeding the catalog idempotent.
     */
    public function existsForTier(string $tier): bool;
}
