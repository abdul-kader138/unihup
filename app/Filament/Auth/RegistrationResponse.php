<?php

namespace App\Filament\Auth;

use App\Filament\Pages\FindUniversities;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * See App\Filament\Auth\LoginResponse — same reasoning, self-registration is
 * the student sign-up flow now, not a staff onboarding flow.
 */
class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(FindUniversities::getUrl());
    }
}
