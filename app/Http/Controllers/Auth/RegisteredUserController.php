<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use Core\BoundedContext\Identity\Application\Actions\RegisterUser;
use Core\BoundedContext\Identity\Application\DTOs\RegisterUserData;
use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(
        RegisterUserRequest $request,
        RegisterUser $registerUser,
        SessionAuthenticator $session,
    ): RedirectResponse {
        $response = $registerUser(RegisterUserData::fromArray($request->validated()));

        // Compat con listeners nativos (envío de email de verificación, etc.).
        event(new Registered(User::findOrFail($response->userId)));

        $session->login(UserId::from($response->userId));

        return redirect(route('dashboard', absolute: false));
    }
}
