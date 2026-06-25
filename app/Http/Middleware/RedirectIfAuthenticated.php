<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Guests (D1 auto-users) must still reach login/register so they
                // can claim or sign into a real account; only fully-registered
                // users are bounced away from the guest-only pages.
                if ($request->user()->isGuest()) {
                    return $next($request);
                }

                return redirect($request->user()->getRedirectUrl());
            }
        }

        return $next($request);
    }
}
