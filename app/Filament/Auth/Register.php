<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

/**
 * Two changes on top of Filament's stock registration page:
 *
 *  - The name field is split into first_name/last_name (User has no `name`
 *    column — see App\Models\User's computed `name` accessor).
 *  - Assigns the default panel role to every self-registered account.
 *    Without this, a new user would authenticate fine but fail
 *    User::canAccessPanel() (which requires a role) and be bounced right
 *    back out — Filament's stock registration flow has no concept of panel
 *    access gating on its own.
 *
 * See App\Filament\Auth\Login for the same panel's login-side customisation.
 */
class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        $number = trim((string) ($data['whatsapp_number'] ?? ''));
        $optedIn = (bool) ($data['whatsapp_opt_in'] ?? false);

        if ($number !== '') {
            $digits = preg_replace('/\D+/', '', $number) ?? '';
            $data['whatsapp_number'] = '+'.$digits;
        }

        $data['whatsapp_opt_in_at'] = $optedIn ? now() : null;

        $user = parent::handleRegistration($data);

        $user->assignRole('panel_user');

        return $user;
    }

    /**
     * No-op on purpose. Filament's default sends
     * Filament\Notifications\Auth\VerifyEmail with a link to the panel's own
     * verify route, which is behind Authenticate middleware and so 302s to
     * /login when the email is opened on another device. We instead rely on
     * the `Registered` event that parent::register() fires: Laravel's
     * always-registered SendEmailVerificationNotification listener calls
     * User::sendEmailVerificationNotification(), which sends
     * App\Notifications\Auth\VerifyEmail (→ `verification.verify`, no auth
     * required). Overriding this to nothing suppresses the duplicate email.
     */
    protected function sendEmailVerificationNotification(Model $user): void
    {
        // Intentionally empty — see the `Registered` event listener.
    }

    /**
     * @return array<int|string, string|Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getFirstNameFormComponent(),
                        $this->getLastNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('whatsapp_number')
                            ->label('Mobile / WhatsApp number')
                            ->tel()
                            ->placeholder('+39 333 123 4567')
                            ->helperText('Optional. Use the full international number, including country code. Spaces and dashes are accepted.')
                            ->requiredIf('whatsapp_opt_in', true)
                            ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                                if (blank($value)) {
                                    return;
                                }

                                $raw = trim((string) $value);
                                $digits = preg_replace('/\D+/', '', $raw) ?? '';

                                if (! str_starts_with($raw, '+') || ! preg_match('/^[1-9]\d{7,14}$/', $digits)) {
                                    $fail('Enter a valid international number in E.164 format, for example +39 333 123 4567.');
                                }
                            }),
                        Toggle::make('whatsapp_opt_in')
                            ->label('I use WhatsApp and agree to receive support messages there')
                            ->helperText('You can change this later from your profile.')
                            ->live(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label(__('First name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label(__('Last name'))
            ->required()
            ->maxLength(255);
    }

    protected function getPasswordFormComponent(): Component
    {
        // Strength itself (min length, mixed case, numbers) is enforced by
        // the Password::default() rule the base component already applies —
        // see AppServiceProvider::boot(). This just surfaces the requirement
        // to the user instead of letting them find out from a rejected form.
        return parent::getPasswordFormComponent()
            ->helperText('At least 8 characters, with uppercase, lowercase, and a number.');
    }
}
