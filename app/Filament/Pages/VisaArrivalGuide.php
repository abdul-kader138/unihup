<?php

namespace App\Filament\Pages;

use App\Support\VisaArrivalCopy;
use Filament\Pages\Page;

/**
 * Standalone explainer for the immigration side of studying in Italy —
 * pre-enrollment, the Type D visa, health insurance, the residence
 * permit, and the tax code. Content lives in App\Support\VisaArrivalCopy
 * (see its doc comment for why this is curated, not per-university data).
 * Distinct from DocumentRecognitionGuide, which covers the separate
 * qualification-recognition process (DOV/CIMEA) — the two are cross-
 * linked since most international applicants need both. Open to every
 * registered user, same as FindUniversities — no HasPageShield/canAccess
 * override, since this is informational content every applicant benefits
 * from, not an admin-managed resource.
 */
class VisaArrivalGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Visa & Arrival';

    protected static ?string $title = 'Visa & Arrival Guide';

    protected static ?string $slug = 'visa-arrival';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.visa-arrival-guide';

    public function getSections(): array
    {
        return VisaArrivalCopy::SECTIONS;
    }

    /**
     * Sections grouped by phase, in App\Support\VisaArrivalCopy::PHASES
     * display order — lets the view render one heading per phase without
     * re-deriving the grouping itself.
     *
     * @return array<string, array{label: string, sections: array}>
     */
    public function getPhasedSections(): array
    {
        $sections = collect(VisaArrivalCopy::SECTIONS)->groupBy('phase');

        return collect(VisaArrivalCopy::PHASES)
            ->mapWithKeys(fn (string $label, string $key) => [
                $key => [
                    'label' => $label,
                    'sections' => $sections->get($key, collect())->all(),
                ],
            ])
            ->filter(fn (array $phase) => $phase['sections'] !== [])
            ->all();
    }

    public function getOfficialLinks(): array
    {
        return VisaArrivalCopy::OFFICIAL_LINKS;
    }
}
