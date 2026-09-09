<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Support\BudgetPlanner as Planner;
use App\Support\CostEstimator as Estimator;
use App\Support\Currencies;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * The whole-journey budget for a saved program: one-off relocation costs plus
 * recurring yearly study + living costs, rolled up to a first-year and a
 * full-course total, optionally shown in the student's home currency at a
 * rate they supply. Indicative only (see App\Support\BudgetPlanner). Open to
 * every panel user; no HasPageShield.
 */
class BudgetPlanner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Budget Planner';

    protected static ?string $title = 'Budget Planner';

    protected static ?string $slug = 'budget-planner';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 26;

    protected static string $view = 'filament.pages.budget-planner';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'program_id' => request()->integer('program') ?: $this->shortlistOptions()->keys()->first(),
            'isee' => 20000,
            'housing' => 'shared',
            'travel_estimate' => null,
            'home_currency' => auth()->user()->home_currency,
            'exchange_rate' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Select::make('program_id')
                    ->label('Program')
                    ->options($this->shortlistOptions())
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->helperText($this->shortlistOptions()->isEmpty()
                        ? 'Save a program from Find Universities first.'
                        : null),
                TextInput::make('isee')
                    ->label('Household ISEE / ISEE Parificato (€ per year)')
                    ->numeric()
                    ->minValue(0)
                    ->step(1000)
                    ->live(debounce: 500),
                Select::make('housing')
                    ->label('Where will you live?')
                    ->options(Estimator::HOUSING_OPTIONS)
                    ->native(false)
                    ->live(),
                TextInput::make('travel_estimate')
                    ->label('Your flight / travel estimate (€, one way)')
                    ->numeric()
                    ->minValue(0)
                    ->step(50)
                    ->placeholder('Leave blank for a rough €250–700')
                    ->live(debounce: 500),
                Select::make('home_currency')
                    ->label('Show a home-currency column')
                    ->options(Currencies::options())
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn (?string $state) => auth()->user()->update([
                        'home_currency' => Currencies::isValid($state) ? $state : null,
                    ])),
                TextInput::make('exchange_rate')
                    ->label(fn (Get $get) => $get('home_currency')
                        ? 'How many '.$get('home_currency').' = €1 today?'
                        : 'Exchange rate (per €1)')
                    ->numeric()
                    ->minValue(0)
                    ->step('any')
                    ->visible(fn (Get $get) => filled($get('home_currency')))
                    ->helperText('You provide the rate so it is current — we do not track live rates.')
                    ->live(debounce: 500),
            ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function shortlistOptions()
    {
        return auth()->user()
            ->shortlistedPrograms()
            ->with('university:id,name,canonical_name')
            ->get()
            ->mapWithKeys(fn (DegreeProgram $p) => [$p->id => "{$p->name} — {$p->university->display_name}"]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResult(): ?array
    {
        $programId = $this->data['program_id'] ?? null;

        if (! $programId) {
            return null;
        }

        $program = DegreeProgram::with('university')->find($programId);

        if (! $program || ! auth()->user()->shortlistItems()->where('degree_program_id', $program->id)->exists()) {
            return null;
        }

        $plan = Planner::plan($program, [
            'isee' => (float) ($this->data['isee'] ?? 20000),
            'housing' => (string) ($this->data['housing'] ?? 'shared'),
            'is_eu_citizen' => (bool) auth()->user()->is_eu_citizen,
            'travel_estimate' => filled($this->data['travel_estimate'] ?? null)
                ? (float) $this->data['travel_estimate']
                : null,
        ]);

        $currency = null;
        $code = $this->data['home_currency'] ?? null;
        $rate = $this->data['exchange_rate'] ?? null;

        if (Currencies::isValid($code) && filled($rate) && (float) $rate > 0) {
            $currency = ['code' => $code, 'name' => Currencies::name($code), 'rate' => (float) $rate];
        }

        return ['program' => $program, 'plan' => $plan, 'currency' => $currency];
    }
}
