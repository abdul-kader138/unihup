<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// The panel is the site's only login/UI now — see App\Filament\Auth\Login
// and App\Filament\Auth\Register. This is the OAuth-only alternative to
// that credentials/2FA flow (Google redirects straight back here, never
// through Filament's Login page).
Route::prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
});
