<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Policy;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Policy\Exceptions\ModuleNotAllowed;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanModules;

/**
 * Domain policy guard for module gating (sidebar/route middleware). Throws
 * ModuleNotAllowed when the tier may not access the module code.
 */
final readonly class EnsureModuleAllowed
{
    public function __construct(private PlanModules $modules = new PlanModules) {}

    /**
     * @throws ModuleNotAllowed
     */
    public function __invoke(PlanTier $tier, string $moduleCode): void
    {
        if (! $this->modules->allows($tier, $moduleCode)) {
            throw ModuleNotAllowed::for($tier, $moduleCode);
        }
    }
}
