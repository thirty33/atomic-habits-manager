<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User as UserModel;
use Closure;
use Core\BoundedContext\Identity\Application\Actions\RegisterGuestUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public-app guest auto-user (D1). When a human first lands on the app surface
 * without being authenticated, a guest {@see \App\Models\User} is created (free
 * plan, is_active=true) and logged into the session so {@see \auth()->id()}
 * resolves for the sidebar and every downstream controller/ViewModel.
 *
 * Scope is intentionally narrow: this middleware is registered ONLY on the
 * `backoffice` app group (not on login/register/logout/verify-email/password,
 * asset, health or api routes), and it only creates a guest on real top-level
 * page navigation — a safe GET/HEAD request that accepts HTML. AJAX/JSON data
 * endpoints, writes, already-authenticated requests and bots hitting arbitrary
 * non-HTML endpoints all pass through untouched, so a guest is created only for
 * a human who actually lands on an app page (and a logged-in real user is never
 * replaced by a guest).
 */
class EnsureGuestUser
{
    public function __construct(private readonly RegisterGuestUser $registerGuestUser) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        if (! $this->isHumanPageNavigation($request)) {
            return $next($request);
        }

        $guest = ($this->registerGuestUser)();

        $model = UserModel::query()->findOrFail($guest->userId);

        Auth::guard('web')->login($model);

        return $next($request);
    }

    /**
     * A guest is auto-created only for a real top-level page visit: a safe
     * GET/HEAD request that asks for HTML, is not an AJAX/JSON data call, and
     * lands on a module page-entry route (`*.index`). The `/json`, board, edit
     * and occurrence data endpoints are excluded, so bots and AJAX polling never
     * spawn guests.
     */
    private function isHumanPageNavigation(Request $request): bool
    {
        if (! $request->isMethodSafe() || $request->expectsJson() || $request->ajax() || ! $request->acceptsHtml()) {
            return false;
        }

        return $request->route()?->named('backoffice.*.index') === true;
    }
}
