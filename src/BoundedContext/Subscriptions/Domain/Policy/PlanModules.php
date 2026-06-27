<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Policy;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Domain policy: which backoffice modules each plan tier may see/use. This is
 * the single place where the module set of a plan lives — change a list here
 * and the sidebar/middleware follow, without touching controllers.
 *
 * Module codes match the Access bounded context module catalog
 * (habits, calendar, reports, atomic_ia, ...). The Access catalog is the source
 * of truth; PlanModulesCatalogDriftTest fails if a code referenced here ever
 * stops existing as an active module in that catalog.
 */
final class PlanModules
{
    private const FREE_MODULES = ['habits', 'calendar', 'reports'];

    private const UNLIMITED_MODULES = ['habits', 'calendar', 'reports', 'atomic_ia'];

    /**
     * The module codes available to a tier.
     *
     * @return list<string>
     */
    public function modulesFor(PlanTier $tier): array
    {
        return $tier->isUnlimited() ? self::UNLIMITED_MODULES : self::FREE_MODULES;
    }

    /**
     * Whether a tier may access a given module code.
     */
    public function allows(PlanTier $tier, string $moduleCode): bool
    {
        return in_array($moduleCode, $this->modulesFor($tier), true);
    }
}
