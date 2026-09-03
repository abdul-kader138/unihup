<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\WhatsAppMediaController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Email verification. Deliberately outside any auth middleware so the link
// works when opened on a device the user isn't logged in on — the `signed`
// middleware is what secures it. Named `verification.verify` so Laravel's
// stock VerifyEmail notification (see App\Notifications\Auth\VerifyEmail)
// points here with no customisation. This URI (/email/verify/...) does not
// collide with Filament's own /email-verification/verify/... route.
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// The panel is the site's only login/UI now — see App\Filament\Auth\Login
// and App\Filament\Auth\Register. This is the OAuth-only alternative to
// that credentials/2FA flow (Google redirects straight back here, never
// through Filament's Login page).
Route::prefix('auth/google')->name('auth.google.')->middleware('throttle:30,1')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
});

// WhatsApp Business Cloud API webhook. No auth — Meta calls this server to
// server. GET is Meta's one-time subscription handshake (hub.verify_token);
// POST delivers inbound messages + delivery/read receipts and is
// authenticated by the X-Hub-Signature-256 header (see the controller).
// CSRF-exempt via bootstrap/app.php.
Route::match(['get', 'post'], '/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp');

// Staff-only: stream inbound WhatsApp media (images/docs a customer sent)
// into the inbox thread. Off the public disk, so it needs an auth check.
Route::get('/whatsapp/media/{message}', WhatsAppMediaController::class)
    ->middleware('auth')
    ->name('whatsapp.media');
