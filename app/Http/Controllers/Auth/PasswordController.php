<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Core\BoundedContext\Identity\Application\Actions\ChangeUserPassword;
use Core\BoundedContext\Identity\Application\DTOs\ChangeUserPasswordData;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, ChangeUserPassword $change): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        try {
            $change(new ChangeUserPasswordData(
                userId: (int) $request->user()->getKey(),
                currentPassword: (string) $validated['current_password'],
                newPassword: (string) $validated['password'],
            ));
        } catch (InvalidCredentials $e) {
            throw ValidationException::withMessages([
                'current_password' => __('The password is incorrect.'),
            ])->errorBag('updatePassword');
        }

        return back()->with('status', 'password-updated');
    }
}
