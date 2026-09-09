<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Support\CostEstimator as Estimator;
use App\Support\FinancialSupportCopy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * A rough first-year cost estimate for a saved program: tuition + living
 * costs minus a likely regional-scholarship offset. Everything is indicative
 * (see App\Support\CostEstimator). Open to every panel user; no HasPageShield.
 */
class CostEstimator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Cost Estimator';

    protected static ?string $title = 'Cost Estimator';

    protected static ?string $slug = 'cost-estimator';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 27;

    protected static string $view = 'filament.pages.cost-estimator';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'program_id' => request()->integer('program') ?: $this->shortlistOptions()->keys()->first(),
            'isee' => 20000,
            'housing' => 'shared',
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
                    ->live(debounce: 500)
                    ->helperText('Your family\'s income+assets indicator. Not sure yet? Leave the default to see a mid-range estimate.'),
                Select::make('housing')
                    ->label('Where will you live?')
                    ->options(Estimator::HOUSING_OPTIONS)
                    ->native(false)
                    ->live(),
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

        $estimate = Estimator::estimate(
            $program,
            (float) ($this->data['isee'] ?? 20000),
            (string) ($this->data['housing'] ?? 'shared'),
        );

        return ['program' => $program, 'estimate' => $estimate];
    }

    public function getIseeBandsNote(): string
    {
        return FinancialSupportCopy::ISEE_FEE_BANDS_NOTE;
    }
}
