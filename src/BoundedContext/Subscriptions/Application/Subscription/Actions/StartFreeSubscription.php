<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Subscription\Actions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * The single application use case owning the "ensure the user's current
 * subscription exists" rule, idempotently. It is the only sanctioned way to
 * create a subscription, so the "one current subscription per user" invariant
 * (modelled by {@see \Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionAlreadyExists})
 * is never violated: if the user already has one it is returned untouched (no
 * 500), otherwise a fresh free subscription is created. The guest auto-user flow
 * relies on this idempotency.
 */
final readonly class StartFreeSubscription
{
    public function __construct(
        private SubscriptionRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $userId): int
    {
        $id = UserId::from($userId);

        $existing = $this->repository->findByUser($id);

        if ($existing !== null) {
            return $existing->id()->value();
        }

        $subscription = Subscription::startFree($id);

        $this->transaction->execute(fn () => $this->repository->save($subscription));

        return $subscription->id()->value();
    }
}
