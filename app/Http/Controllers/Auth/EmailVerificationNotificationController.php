<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Core\BoundedContext\Identity\Application\Actions\ResendEmailVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, ResendEmailVerification $resend): RedirectResponse
    {
        $sent = $resend((int) $request->user()->getKey());

        if (! $sent && $request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'verification-link-sent');
    }
}
