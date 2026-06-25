<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent;

use App\Models\Plan as PlanModel;
use App\Models\Subscription as SubscriptionModel;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Plan\Exceptions\PlanNotFound;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanPolicy;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;

/**
 * Data Mapper between the Plan aggregate and App\Models\Plan. Implements the
 * write-side (PlanRepository) and the single application read port
 * (PlanCatalogReader) — the latter composes the user's subscription with the
 * price catalog and the domain policy.
 */
final readonly class EloquentPlanRepository implements PlanCatalogReader, PlanRepository
{
    public function __construct(
        private SubscriptionRepository $subscriptions,
        private PlanPolicy $policy = new PlanPolicy,
    ) {}

    public function save(Plan $plan): void
    {
        $model = $plan->hasId()
            ? PlanModel::query()->findOrFail($plan->id()->value())
            : new PlanModel;

        $model->fill([
            'tier' => $plan->tier()->value(),
            'amount' => $plan->amount()->value(),
            'currency' => $plan->currency()->value(),
            'is_active' => $plan->isActive(),
        ])->save();

        if (! $plan->hasId()) {
            $plan->assignId(PlanId::from((int) $model->getKey()));
        }
    }

    public function findByTier(PlanTier $tier): Plan
    {
        $model = PlanModel::query()->where('tier', $tier->value())->first();

        if ($model === null) {
            throw PlanNotFound::forTier($tier);
        }

        return Plan::fromPrimitives(
            id: PlanId::from((int) $model->getKey()),
            tier: PlanTier::from((string) $model->tier),
            amount: Amount::from((float) $model->amount),
            currency: Currency::from((string) $model->currency),
            isActive: (bool) $model->is_active,
        );
    }

    public function existsForTier(string $tier): bool
    {
        return PlanModel::query()->where('tier', $tier)->exists();
    }

    public function tierOf(UserId $userId): PlanTier
    {
        $subscription = $this->subscriptions->findByUser($userId);

        return $subscription?->planTier() ?? PlanTier::free();
    }

    public function tiersOf(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return SubscriptionModel::query()
            ->whereIn('user_id', $userIds)
            ->pluck('plan_tier', 'user_id')
            ->map(static fn (string $tier): string => $tier)
            ->all();
    }

    public function planInfo(PlanTier $tier): array
    {
        $price = $this->priceOf($tier->value());

        return [
            'tier' => $tier->value(),
            'amount' => $price['amount'] ?? 0.0,
            'currency' => $price['currency'] ?? 'USDT',
            'modules' => $this->policy->modulesFor($tier),
            'max_habits' => $this->policy->maxHabits($tier),
        ];
    }

    /**
     * Price info for a tier from the catalog, or null when the tier is not
     * seeded. Internal helper for {@see self::planInfo()}; not part of the read
     * port.
     *
     * @return array{amount: float, currency: string, is_active: bool}|null
     */
    private function priceOf(string $tier): ?array
    {
        $model = PlanModel::query()->where('tier', $tier)->first();

        if ($model === null) {
            return null;
        }

        return [
            'amount' => (float) $model->amount,
            'currency' => (string) $model->currency,
            'is_active' => (bool) $model->is_active,
        ];
    }
}
