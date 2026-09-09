<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Support\ProficiencyLevels;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * First-run wizard: collect the study profile in three short steps so the
 * journey checklist and the "can I apply?" hints are personalised from the
 * start. New accounts land here (see RegistrationResponse / GoogleAuthController);
 * it is also reachable from the My Journey nudge. Open to every panel user;
 * no HasPageShield, hidden from the sidebar.
 */
class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Get started';

    protected static ?string $slug = 'get-started';

    protected static string $view = 'filament.pages.onboarding';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'nationality' => $user->nationality,
            'prior_education_country' => $user->prior_education_country,
            'is_eu_citizen' => (bool) $user->is_eu_citizen,
            'english_level' => $user->english_level,
            'italian_level' => $user->italian_level,
            'preferred_subject_id' => $user->preferred_subject_id,
            'preferred_degree_level' => $user->preferred_degree_level,
            'scholarship_interest' => (bool) $user->scholarship_interest,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Wizard::make([
                    Step::make('About you')
                        ->icon('heroicon-o-user')
                        ->description('Where you are coming from')
                        ->schema([
                            TextInput::make('nationality')
                                ->label('Your nationality')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('prior_education_country')
                                ->label('Country of your last qualification')
                                ->maxLength(100),
                            Toggle::make('is_eu_citizen')
                                ->label('I hold EU / EEA / Swiss citizenship')
                                ->helperText('If this is off, your checklist adds the pre-enrolment, visa and residence-permit steps.'),
                        ]),
                    Step::make('Languages')
                        ->icon('heroicon-o-language')
                        ->description('So we can flag language requirements')
                        ->schema([
                            Select::make('english_level')
                                ->label('English level')
                                ->options(ProficiencyLevels::options())
                                ->native(false),
                            Select::make('italian_level')
                                ->label('Italian level')
                                ->options(ProficiencyLevels::options())
                                ->native(false),
                        ]),
                    Step::make('Your plan')
                        ->icon('heroicon-o-academic-cap')
                        ->description('What you want to study')
                        ->schema([
                            Select::make('preferred_subject_id')
                                ->label('Subject you want to study')
                                ->options(fn () => Subject::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),
                            Select::make('preferred_degree_level')
                                ->label('Degree level')
                                ->options(DegreeProgram::DEGREE_LEVELS)
                                ->native(false),
                            Toggle::make('scholarship_interest')
                                ->label('I want to apply for scholarships / right-to-study (DSU) benefits'),
                        ]),
                ])
                    ->submitAction(view('filament.pages.partials.onboarding-submit')),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $user->update([
            'nationality' => $data['nationality'],
            'prior_education_country' => $data['prior_education_country'] ?? null,
            'is_eu_citizen' => (bool) ($data['is_eu_citizen'] ?? false),
            'english_level' => $data['english_level'] ?? null,
            'italian_level' => $data['italian_level'] ?? null,
            'preferred_subject_id' => $data['preferred_subject_id'] ?? null,
            'preferred_degree_level' => $data['preferred_degree_level'] ?? null,
            'scholarship_interest' => (bool) ($data['scholarship_interest'] ?? false),
            'study_profile_completed_at' => $user->study_profile_completed_at ?? now(),
        ]);

        Notification::make()
            ->title("You're all set")
            ->body('Your journey checklist and eligibility hints are now personalised.')
            ->success()
            ->send();

        $this->redirect(FindUniversities::getUrl());
    }
}
