<?php

namespace App\Filament\Auth;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MyJourney;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * The panel is the site's only login (see App\Filament\Auth\Login), and
 * most people signing in are students, not staff — land them on their
 * personal journey home (My Journey). Staff go to the dashboard.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $home = Dashboard::canAccess() ? Dashboard::getUrl() : MyJourney::getUrl();

        return redirect()->intended($home);
    }
}
