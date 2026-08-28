<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Customer-facing verification notification. Laravel's `Registered` event
 * listener (Illuminate\Auth\Listeners\SendEmailVerificationNotification)
 * calls User::sendEmailVerificationNotification(), which sends this; the
 * "resend" button on the verification prompt also routes through the User
 * model method (see App\Filament\Auth\EmailVerificationPrompt).
 *
 * The base class's verificationUrl() builds the `verification.verify` route,
 * which App\Http\Controllers\Auth\VerifyEmailController defines in
 * routes/web.php WITHOUT an auth-session requirement — so the link works
 * from any device. Kept as its own subclass only so it can be queued and so
 * mail copy/branding can diverge from the framework default later.
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use InteractsWithQueue;
}
