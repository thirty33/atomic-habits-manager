<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Core\BoundedContext\Identity\Application\Actions\AuthenticateUser;
use Core\BoundedContext\Identity\Application\Actions\LogoutUser;
use Core\BoundedContext\Identity\Application\DTOs\AuthenticateUserData;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AuthenticateUser $authenticate): RedirectResponse
    {
        try {
            $response = $authenticate(AuthenticateUserData::fromArray([
                'email' => $request->string('email')->value(),
                'password' => $request->string('password')->value(),
                'remember' => $request->boolean('remember'),
                'ip_address' => $request->ip() ?? '',
            ]));
        } catch (InvalidCredentials $e) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        } catch (UserNotActive $e) {
            throw ValidationException::withMessages(['email' => __('Tu cuenta está inactiva.')]);
        }

        return redirect()->intended($response->redirectUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, LogoutUser $logout): RedirectResponse
    {
        if ($request->user() !== null) {
            $logout((int) $request->user()->getKey());
        }

        return redirect('/');
    }
}
