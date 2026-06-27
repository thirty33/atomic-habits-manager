<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanId;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\AggregateRoot;
use LogicException;

/**
 * Aggregate Root of a subscription plan (free/unlimited): the configurable
 * price catalog entry. Capabilities/limits of each tier are NOT stored here —
 * they live in the Policy (PlanLimits/PlanModules). This aggregate only owns
 * the tier identity and its price. Pure domain: no Eloquent.
 */
final class Plan extends AggregateRoot
{
    private function __construct(
        private ?PlanId $id,
        private PlanTier $tier,
        private Amount $amount,
        private Currency $currency,
        private bool $isActive,
    ) {}

    public static function create(PlanTier $tier, Amount $amount, Currency $currency, bool $isActive = true): self
    {
        return new self(id: null, tier: $tier, amount: $amount, currency: $currency, isActive: $isActive);
    }

    public static function fromPrimitives(
        PlanId $id,
        PlanTier $tier,
        Amount $amount,
        Currency $currency,
        bool $isActive,
    ): self {
        return new self(id: $id, tier: $tier, amount: $amount, currency: $currency, isActive: $isActive);
    }

    public function assignId(PlanId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Plan already has an id.');
        }

        $this->id = $id;
    }

    public function id(): PlanId
    {
        return $this->id ?? throw new LogicException('Plan has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function tier(): PlanTier
    {
        return $this->tier;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
