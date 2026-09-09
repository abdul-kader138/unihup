<?php

namespace App\Filament\Auth;

use App\Filament\Pages\Onboarding;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * See App\Filament\Auth\LoginResponse — self-registration is the student
 * sign-up flow, so land brand-new accounts on the first-run onboarding
 * wizard, which then drops them into Find Universities.
 */
class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(Onboarding::getUrl());
    }
}
