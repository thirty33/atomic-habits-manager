<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Core\BoundedContext\Access\Application\Authorization\Authorize;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Gates the whole Users management module (listing + activation + payment
 * reconciliation) to the superadmin. Authorization goes through the Access BC
 * Authorize use case, never the framework Gate: a user may manage users when
 * they hold backoffice.admin (superadmin bypass) or the users.view capability.
 * Without this, any authenticated user — including auto-created guests — could
 * read every user and toggle activation.
 */
class EnsureCanManageUsers
{
    public function __construct(private readonly Authorize $authorize) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) ($request->user()?->getAuthIdentifier() ?? 0);

        $allowed = $userId > 0 && (
            ($this->authorize)($userId, Capability::BackofficeAdmin)
            || ($this->authorize)($userId, Capability::UsersView)
        );

        if (! $allowed) {
            throw new AccessDeniedHttpException(__('No tienes permiso para esta acción.'));
        }

        return $next($request);
    }
}
