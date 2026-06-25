<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Core\BoundedContext\Access\Application\Authorization\Authorize;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanModules;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: blocks a backoffice route group whose module the user's plan
 * tier does not allow (e.g. atomic_ia is unlimited-only). The superadmin
 * (backoffice.admin capability) bypasses the plan gate entirely. Excluded users
 * get a 403 JSON for XHR or a redirect for browser navigation.
 *
 * Usage: ->middleware('module:atomic_ia')
 */
final class EnsureModuleAllowed
{
    public function __construct(
        private readonly Authorize $authorize,
        private readonly PlanCatalogReader $planReader,
        private readonly PlanModules $modules = new PlanModules,
    ) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $userId = $request->user()?->user_id;

        if ($userId === null) {
            return $next($request);
        }

        if (($this->authorize)((int) $userId, Capability::BackofficeAdmin)) {
            return $next($request);
        }

        $tier = $this->planReader->tierOf(UserId::from((int) $userId));

        if ($this->modules->allows($tier, $module)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Tu plan no incluye este módulo.')], 403);
        }

        return redirect()->route('backoffice.habits.index');
    }
}
