<?php

namespace App\Filament\Auth;

use App\Filament\Pages\FindUniversities;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * The panel is the site's only login (see App\Filament\Auth\Login), and
 * most people signing in are students browsing programs rather than staff —
 * land them on the university search page instead of the admin dashboard.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(FindUniversities::getUrl());
    }
}
