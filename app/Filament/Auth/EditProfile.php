<?php

namespace App\Filament\Auth;

use App\Models\Setting;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;

/**
 * Adds an avatar upload and 2FA self-service management on top of Filament's
 * stock profile page — the enable/disable/recovery-code actions live here
 * (getHeaderActions()) rather than a dedicated route, matching the pattern
 * of everything else on this page being "one self-contained profile screen."
 * See App\Filament\Auth\Login for the other half (the login-time challenge).
 */
class EditProfile extends BaseEditProfile
{
    /**
     * @return array<int|string, string|Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Grid::make(3)->schema([
                            Section::make('Profile Picture')
                                ->schema([
                                    FileUpload::make('avatar')
                                        ->label('')
                                        ->avatar()
                                        ->imageEditor()
                                        ->circleCropper()
                                        ->disk('public')
                                        ->directory('avatars')
                                        ->visibility('public'),
                                ])
                                ->columnSpan(1),

                            Section::make('Personal Information')
                                ->schema([
                                    Grid::make(2)->schema([
                                        $this->getFirstNameFormComponent(),
                                        $this->getLastNameFormComponent(),
                                    ]),
                                    $this->getEmailFormComponent(),
                                ])
                                ->columnSpan(2),
                        ]),

                        Section::make('Change Password')
                            ->schema([
                                Grid::make(2)->schema([
                                    $this->getPasswordFormComponent(),
                                    $this->getPasswordConfirmationFormComponent(),
                                ]),
                            ]),

                        Section::make('WhatsApp Support')
                            ->description('Chat with our advisors on WhatsApp. We only message you about your enquiries — you can opt out any time.')
                            ->schema([
                                Select::make('whatsapp_country_code')
                                    ->label('WhatsApp country code')
                                    ->options(Register::COUNTRY_CODES)
                                    ->searchable()
                                    ->default('+39')
                                    ->requiredIf('whatsapp_opt_in', true),
                                TextInput::make('whatsapp_local_number')
                                    ->label('WhatsApp number')
                                    ->tel()
                                    ->placeholder('333 123 4567')
                                    ->helperText('Enter the national number, including the leading 0 where your country uses one.')
                                    ->required(fn (Get $get): bool => (bool) $get('whatsapp_opt_in'))
                                    ->live()
                                    ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        if (blank($value)) {
                                            return;
                                        }

                                        $country = preg_replace('/\D+/', '', (string) $get('whatsapp_country_code')) ?? '';
                                        $local = preg_replace('/\D+/', '', (string) $value) ?? '';
                                        $allowedLengths = Register::NATIONAL_NUMBER_LENGTHS['+'.$country] ?? [];
                                        $significant = ltrim($local, '0');

                                        if ($local === '' || $significant === '' || ! preg_match('/^\d+$/', $local) || ! in_array(strlen($local), $allowedLengths, true) || strlen($country.$significant) > 15) {
                                            $fail('Enter a valid WhatsApp number for the selected country.');
                                        }
                                    }),
                                Toggle::make('whatsapp_opt_in')
                                    ->label('Allow UniHup to contact me on WhatsApp')
                                    ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        if (filled($get('whatsapp_local_number')) && ! $value) {
                                            $fail('Please acknowledge WhatsApp contact to save your number.');
                                        }
                                    })
                                    ->live(),
                            ]),

                        Section::make('Two-Factor Authentication')
                            ->schema([
                                Placeholder::make('two_factor_status')
                                    ->label('')
                                    ->content(fn () => $this->twoFactorStatusText()),
                            ]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }

    // Stamp/clear the opt-in timestamp from the toggle, and normalise the
    // number to bare E.164 digits-with-plus so it matches what Meta reports
    // on inbound webhooks (see App\Jobs\ProcessInboundWhatsAppJob).
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $countryCode = preg_replace('/\D+/', '', (string) ($data['whatsapp_country_code'] ?? '')) ?? '';
        $localNumber = preg_replace('/\D+/', '', (string) ($data['whatsapp_local_number'] ?? '')) ?? '';

        if ($countryCode !== '' && $localNumber !== '') {
            $data['whatsapp_number'] = '+'.$countryCode.ltrim($localNumber, '0');
        } else {
            $data['whatsapp_number'] = null;
        }

        $optedIn = (bool) ($data['whatsapp_opt_in'] ?? false);
        $data['whatsapp_opt_in_at'] = $optedIn
            ? ($this->getUser()->whatsapp_opt_in_at ?? now())
            : null;

        unset($data['whatsapp_country_code'], $data['whatsapp_local_number']);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $digits = preg_replace('/\D+/', '', (string) ($data['whatsapp_number'] ?? '')) ?? '';
        $countryCode = collect(array_keys(Register::COUNTRY_CODES))
            ->sortByDesc(fn (string $code) => strlen($code))
            ->first(fn (string $code) => str_starts_with($digits, ltrim($code, '+')));

        $data['whatsapp_country_code'] = $countryCode ?: '+39';
        $data['whatsapp_local_number'] = $countryCode
            ? substr($digits, strlen(ltrim($countryCode, '+')))
            : $digits;

        return $data;
    }

    // User has no `name` column (see App\Models\User's computed `name`
    // accessor) — first_name/last_name replace the base class's single
    // getNameFormComponent().
    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label('First name')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last name')
            ->required()
            ->maxLength(255);
    }

    // Strength itself (min length, mixed case, numbers) is enforced by the
    // Password::default() rule the base component already applies — see
    // AppServiceProvider::boot(). This just surfaces the requirement here
    // too, instead of letting the user find out from a rejected form.
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText('Leave blank to keep your current password. At least 8 characters, with uppercase, lowercase, and a number.');
    }

    private function twoFactorStatusText(): string
    {
        if (! $this->twoFactorGloballyEnabled()) {
            return 'Two-factor authentication is currently disabled application-wide by an administrator.';
        }

        return $this->getUser()->hasEnabledTwoFactorAuthentication()
            ? 'Two-factor authentication is enabled on your account.'
            : 'Two-factor authentication is not enabled on your account.';
    }

    private function twoFactorGloballyEnabled(): bool
    {
        return (bool) Setting::get('two_factor_enabled', true);
    }

    /**
     * All four actions are always returned (never conditionally omitted)
     * and gated purely via ->visible() closures — deliberately, so a test
     * (or anything else introspecting actions) can assert visibility
     * rather than existence. Only the "globally disabled" case is a real
     * absence, since there is nothing to manage at all in that state.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        if (! $this->twoFactorGloballyEnabled()) {
            return [];
        }

        /** @var User $user */
        $user = $this->getUser();
        $isEnabled = fn () => $user->fresh()->hasEnabledTwoFactorAuthentication();

        return [
            $this->enableTwoFactorAction($user)->visible(fn () => ! $isEnabled()),

            Action::make('showRecoveryCodes')
                ->label('Show Recovery Codes')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->visible($isEnabled)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.auth.recovery-codes-modal', [
                    'codes' => $user->two_factor_recovery_codes ?? [],
                ])),

            Action::make('regenerateRecoveryCodes')
                ->label('Regenerate Recovery Codes')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible($isEnabled)
                ->requiresConfirmation()
                ->modalDescription('Your existing recovery codes will stop working immediately.')
                ->action(function () use ($user) {
                    $user->forceFill([
                        'two_factor_recovery_codes' => app(TwoFactorAuthenticationService::class)->generateRecoveryCodes(),
                    ])->save();

                    Notification::make()->success()->title('Recovery codes regenerated')->send();
                }),

            Action::make('disableTwoFactor')
                ->label('Disable Two-Factor Authentication')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->visible($isEnabled)
                ->requiresConfirmation()
                ->modalHeading('Disable two-factor authentication?')
                ->modalDescription('You will no longer be asked for a code when signing in.')
                ->action(function () use ($user) {
                    $user->forceFill([
                        'two_factor_secret' => null,
                        'two_factor_recovery_codes' => null,
                        'two_factor_confirmed_at' => null,
                    ])->save();

                    Notification::make()->success()->title('Two-factor authentication disabled')->send();
                }),
        ];
    }

    private function enableTwoFactorAction(User $user): Action
    {
        $service = app(TwoFactorAuthenticationService::class);

        return Action::make('enableTwoFactor')
            ->label('Enable Two-Factor Authentication')
            ->icon('heroicon-o-shield-check')
            ->fillForm(fn () => ['secret' => $service->generateSecretKey()])
            ->form([
                Hidden::make('secret'),
                Placeholder::make('qr_code')
                    ->label('Scan this with your authenticator app')
                    ->content(fn (Get $get) => new HtmlString(
                        '<div style="display:flex;flex-direction:column;align-items:center;gap:.5rem;">'
                        .'<img src="'.$service->qrCodeSvg($user, (string) $get('secret')).'" alt="Two-factor setup QR code" />'
                        .'<code style="font-size:.75rem;">'.e((string) $get('secret')).'</code>'
                        .'</div>'
                    )),
                TextInput::make('code')
                    ->label('Authentication Code')
                    ->helperText('Enter the 6-digit code your app just generated to confirm setup.')
                    ->required()
                    ->autocomplete('one-time-code')
                    ->extraInputAttributes(['inputmode' => 'numeric'])
                    ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($service, $get) {
                        if (! $service->verify((string) $get('secret'), (string) $value)) {
                            $fail('The provided code was invalid.');
                        }
                    }),
            ])
            ->action(function (array $data) use ($user, $service) {
                $user->forceFill([
                    'two_factor_secret' => $data['secret'],
                    'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
                    'two_factor_confirmed_at' => now(),
                ])->save();

                Notification::make()->success()->title('Two-factor authentication enabled')->send();
            });
    }
}
