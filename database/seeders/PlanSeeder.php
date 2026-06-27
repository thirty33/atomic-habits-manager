<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Plan\Plan;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Illuminate\Database\Seeder;

/**
 * Provisions the plan price catalog: a free plan (0) and an unlimited plan at
 * the configured placeholder price. Idempotent: re-running it creates no
 * duplicate tier. The actual unlimited price is set by the operator — adjust
 * the constant below.
 */
class PlanSeeder extends Seeder
{
    /** Placeholder price for the unlimited plan — operator sets the real value. */
    public const UNLIMITED_AMOUNT = 5.00;

    public const CURRENCY = 'USDT';

    public function run(PlanRepository $plans, PlanCatalogReader $reader): void
    {
        $this->seedPlan($plans, $reader, PlanTier::free(), Amount::from(0.0));
        $this->seedPlan($plans, $reader, PlanTier::unlimited(), Amount::from(self::UNLIMITED_AMOUNT));
    }

    private function seedPlan(PlanRepository $plans, PlanCatalogReader $reader, PlanTier $tier, Amount $amount): void
    {
        if ($reader->existsForTier($tier->value())) {
            return;
        }

        $plans->save(Plan::create(
            tier: $tier,
            amount: $amount,
            currency: Currency::from(self::CURRENCY),
            isActive: true,
        ));
    }
}
