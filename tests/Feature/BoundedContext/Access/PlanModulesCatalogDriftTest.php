<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Access;

use App\Models\Module as ModuleModel;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanModules;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #10 — drift guard. PlanModules (Subscriptions BC) hardcodes the module codes a
 * plan tier may use; the Access BC module catalog (seeded by AccessSeeder) is the
 * source of truth for module codes. This test fails if PlanModules ever
 * references a code that does not exist as an ACTIVE module in the catalog,
 * forcing the two to stay reconciled.
 */
class PlanModulesCatalogDriftTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_module_referenced_by_plan_modules_exists_as_an_active_module_in_the_access_catalog(): void
    {
        $this->seed(AccessSeeder::class);

        $catalogCodes = ModuleModel::query()
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        $planModules = new PlanModules;

        $referencedCodes = [];
        foreach ($this->allTiers() as $tier) {
            $referencedCodes = array_merge($referencedCodes, $planModules->modulesFor($tier));
        }
        $referencedCodes = array_values(array_unique($referencedCodes));

        $this->assertNotEmpty($referencedCodes, 'PlanModules referenced no module codes.');

        foreach ($referencedCodes as $code) {
            $this->assertContains(
                $code,
                $catalogCodes,
                "PlanModules references module code [{$code}] which is not an active module in the Access catalog.",
            );
        }
    }

    /**
     * @return list<PlanTier>
     */
    private function allTiers(): array
    {
        return [PlanTier::free(), PlanTier::unlimited()];
    }
}
