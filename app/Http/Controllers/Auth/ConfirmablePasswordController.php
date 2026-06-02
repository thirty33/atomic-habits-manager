<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Core\BoundedContext\Identity\Application\Actions\ConfirmUserPassword;
use Core\BoundedContext\Identity\Application\DTOs\ConfirmUserPasswordData;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request, ConfirmUserPassword $confirm): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        try {
            $confirm(new ConfirmUserPasswordData(
                userId: (int) $request->user()->getKey(),
                password: $request->string('password')->value(),
            ));
        } catch (InvalidCredentials $e) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
