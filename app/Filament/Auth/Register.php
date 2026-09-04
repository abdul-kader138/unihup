<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
    /** @var array<string, string> */
    private const COUNTRY_CODES = [
        '+39' => 'Italy (+39)', '+44' => 'United Kingdom (+44)', '+1' => 'United States / Canada (+1)',
        '+33' => 'France (+33)', '+49' => 'Germany (+49)', '+34' => 'Spain (+34)', '+351' => 'Portugal (+351)',
        '+31' => 'Netherlands (+31)', '+32' => 'Belgium (+32)', '+41' => 'Switzerland (+41)',
        '+43' => 'Austria (+43)', '+30' => 'Greece (+30)', '+48' => 'Poland (+48)', '+40' => 'Romania (+40)',
        '+359' => 'Bulgaria (+359)', '+385' => 'Croatia (+385)', '+381' => 'Serbia (+381)',
        '+90' => 'Türkiye (+90)', '+380' => 'Ukraine (+380)', '+7' => 'Russia / Kazakhstan (+7)',
        '+91' => 'India (+91)', '+92' => 'Pakistan (+92)', '+880' => 'Bangladesh (+880)',
        '+86' => 'China (+86)', '+81' => 'Japan (+81)', '+82' => 'South Korea (+82)',
        '+61' => 'Australia (+61)', '+64' => 'New Zealand (+64)', '+55' => 'Brazil (+55)',
        '+52' => 'Mexico (+52)', '+54' => 'Argentina (+54)', '+27' => 'South Africa (+27)',
        '+20' => 'Egypt (+20)', '+212' => 'Morocco (+212)', '+216' => 'Tunisia (+216)',
        '+234' => 'Nigeria (+234)', '+254' => 'Kenya (+254)', '+971' => 'United Arab Emirates (+971)',
        '+966' => 'Saudi Arabia (+966)', '+972' => 'Israel (+972)', '+65' => 'Singapore (+65)',
        '+60' => 'Malaysia (+60)', '+66' => 'Thailand (+66)', '+62' => 'Indonesia (+62)',
    ];

    protected function handleRegistration(array $data): Model
    {
        $countryCode = preg_replace('/\D+/', '', (string) ($data['whatsapp_country_code'] ?? '')) ?? '';
        $localNumber = preg_replace('/\D+/', '', (string) ($data['whatsapp_local_number'] ?? '')) ?? '';
        $optedIn = (bool) ($data['whatsapp_opt_in'] ?? false);

        if ($optedIn && $countryCode !== '' && $localNumber !== '') {
            $data['whatsapp_number'] = '+'.$countryCode.$localNumber;
        } else {
            $data['whatsapp_number'] = null;
        }

        $data['whatsapp_opt_in_at'] = $optedIn ? now() : null;
        unset($data['whatsapp_country_code'], $data['whatsapp_local_number']);

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
                        Select::make('whatsapp_country_code')
                            ->label('WhatsApp country code')
                            ->options(self::COUNTRY_CODES)
                            ->searchable()
                            ->default('+39')
                            ->requiredIf('whatsapp_opt_in', true),
                        TextInput::make('whatsapp_local_number')
                            ->label('WhatsApp number')
                            ->tel()
                            ->placeholder('333 123 4567')
                            ->helperText('Optional. Enter the number without the country code or leading trunk 0.')
                            ->requiredIf('whatsapp_opt_in', true)
                            ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                if (blank($value)) {
                                    return;
                                }

                                $country = preg_replace('/\D+/', '', (string) $get('whatsapp_country_code')) ?? '';
                                $local = preg_replace('/\D+/', '', (string) $value) ?? '';

                                if ($local === '' || str_starts_with($local, '0') || ! preg_match('/^[1-9]\d{5,14}$/', $local) || strlen($country.$local) > 15) {
                                    $fail('Enter a valid WhatsApp number for the selected country.');
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
