<?php

namespace App\Http\Controllers\Auth;

use App\Filament\Pages\FindUniversities;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Email-verification endpoint that does NOT require an existing login
 * session — unlike Filament's own verify route
 * (filament.admin.auth.email-verification.verify), which is wrapped in the
 * panel's Authenticate middleware and so 302s to /login whenever the link
 * is opened on a device where the user isn't signed in (i.e. almost always,
 * since people open verification emails on their phone).
 *
 * Security is unchanged: the route carries Laravel's `signed` middleware,
 * so the URL cannot be forged or tampered with, and the {hash} segment is
 * sha1(user email) — a link stops working the moment the address changes.
 *
 * Linked from App\Notifications\Auth\VerifyEmail via the `verification.verify`
 * route name (Laravel's default), so the base notification's verificationUrl()
 * resolves here with no override needed.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Log the user in on this device so they land straight in the panel
        // instead of on the login screen right after verifying.
        if (! Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->intended(FindUniversities::getUrl());
    }
}
