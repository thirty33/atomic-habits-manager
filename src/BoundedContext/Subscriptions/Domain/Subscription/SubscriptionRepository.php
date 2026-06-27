<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionNotFound;

/**
 * Write-side contract for the user's current subscription. Bare DB operations
 * only — the transaction boundary lives in the Application use case
 * (TransactionManager), never here.
 */
interface SubscriptionRepository
{
    public function save(Subscription $subscription): void;

    public function findByUser(UserId $userId): ?Subscription;

    /** Whether the user already has a current subscription (the "one per user" invariant). */
    public function existsForUser(UserId $userId): bool;

    /** @throws SubscriptionNotFound */
    public function getByUser(UserId $userId): Subscription;
}
