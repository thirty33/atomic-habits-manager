<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent;

use App\Models\Subscription as SubscriptionModel;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionAlreadyExists;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions\SubscriptionNotFound;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Core\Shared\Domain\Bus\DomainEventBus;

/**
 * Data Mapper between the Subscription aggregate and App\Models\Subscription.
 * One current subscription per user (unique user_id): save upserts on user_id,
 * assigns the id on insert, flushes the events staged by transitions, then pulls
 * and publishes all accumulated domain events through the bus (mirrors
 * EloquentPaymentRepository). The transaction boundary lives in the Application
 * use case (TransactionManager); the publish runs within that outer transaction.
 */
final readonly class EloquentSubscriptionRepository implements SubscriptionRepository
{
    public function __construct(private DomainEventBus $bus) {}

    public function save(Subscription $subscription): void
    {
        $model = SubscriptionModel::query()
            ->where('user_id', $subscription->userId()->value())
            ->first();

        // The "one current subscription per user" invariant: a brand-new (id-less)
        // subscription may not be persisted when a row already exists for the user.
        // The DB unique index on user_id is the persistence-level backstop.
        if (! $subscription->hasId() && $model !== null) {
            throw SubscriptionAlreadyExists::forUser($subscription->userId());
        }

        $model ??= new SubscriptionModel;

        $model->fill([
            'user_id' => $subscription->userId()->value(),
            'plan_tier' => $subscription->planTier()->value(),
            'status' => $subscription->status()->value(),
        ])->save();

        if (! $subscription->hasId()) {
            $subscription->assignId(SubscriptionId::from((int) $model->getKey()));
        }

        $subscription->recordEventsAfterAssign();

        $this->bus->publish(...$subscription->pullDomainEvents());
    }

    public function findByUser(UserId $userId): ?Subscription
    {
        $model = SubscriptionModel::query()->where('user_id', $userId->value())->first();

        if ($model === null) {
            return null;
        }

        return Subscription::fromPrimitives(
            id: SubscriptionId::from((int) $model->getKey()),
            userId: UserId::from((int) $model->user_id),
            planTier: PlanTier::from((string) $model->plan_tier),
            status: SubscriptionStatus::from((string) $model->status),
        );
    }

    public function existsForUser(UserId $userId): bool
    {
        return SubscriptionModel::query()->where('user_id', $userId->value())->exists();
    }

    public function getByUser(UserId $userId): Subscription
    {
        return $this->findByUser($userId) ?? throw SubscriptionNotFound::forUser($userId);
    }
}
