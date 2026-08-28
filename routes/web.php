<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
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
Route::prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
});
