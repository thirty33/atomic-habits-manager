<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Core\BoundedContext\Identity\Application\Actions\MarkEmailAsVerified;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request, MarkEmailAsVerified $markVerified): RedirectResponse
    {
        $wasUnverified = $markVerified((int) $request->user()->getKey());

        if ($wasUnverified) {
            // Compat con listeners nativos.
            event(new Verified($request->user()->fresh()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
