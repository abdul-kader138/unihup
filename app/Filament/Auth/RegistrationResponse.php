<?php

namespace App\Filament\Auth;

use App\Filament\Pages\MyJourney;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * See App\Filament\Auth\LoginResponse — self-registration is the student
 * sign-up flow, so land new accounts on their personal journey home.
 */
class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(MyJourney::getUrl());
    }
}
