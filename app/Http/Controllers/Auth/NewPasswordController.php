<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Core\BoundedContext\Identity\Application\Actions\ResetUserPassword;
use Core\BoundedContext\Identity\Application\DTOs\ResetUserPasswordData;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request, ResetUserPassword $reset): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = $reset(ResetUserPasswordData::fromArray(
            $request->only('email', 'password', 'token'),
        ));

        if ($status === Password::PASSWORD_RESET) {
            // Compat con listeners nativos.
            $eloquent = User::where('email', $request->string('email')->value())->first();
            if ($eloquent !== null) {
                event(new PasswordReset($eloquent));
            }

            return redirect()->route('login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
