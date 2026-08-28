<?php

namespace App\Notifications\Auth;

use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Customer-facing verification notification. Laravel's `Registered` event
 * listener (Illuminate\Auth\Listeners\SendEmailVerificationNotification)
 * calls User::sendEmailVerificationNotification(), which sends this.
 *
 * The base class's verificationUrl() targets a route named
 * `verification.verify`, which this app does not define — it's a Filament
 * panel, so the verification route is
 * `filament.admin.auth.email-verification.verify`. We build that signed URL
 * via the admin panel, matching Filament\Notifications\Auth\VerifyEmail.
 * Resolving through the named panel (not the "current" one) keeps this
 * working from the queue worker, which has no request/panel context.
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use InteractsWithQueue;

    protected function verificationUrl($notifiable): string
    {
        $panel = Filament::getCurrentPanel() ?? Filament::getPanel('admin');

        return $panel->getVerifyEmailUrl($notifiable);
    }
}
