<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces that the authenticated user is active on every backoffice request
 * (D3). If a user is deactivated mid-session, their live session is killed and
 * they are redirected to login (or get a 403 for JSON requests). Active users
 * pass through untouched.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => __('Tu cuenta ha sido desactivada.')], 403);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
