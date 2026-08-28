<?php

namespace App\Filament\Pages;

use App\Support\AdmissionTestCopy;
use Filament\Pages\Page;

/**
 * Standalone explainer for Italy's standardized admission tests (TOLC,
 * IMAT, and the semestre filtro reform for Italian-taught Medicine) —
 * content lives in App\Support\AdmissionTestCopy since this is uniform
 * national process, not per-university data (see that class's doc
 * comment). Open to every registered user, same as FindUniversities — no
 * HasPageShield/canAccess override, since this is informational content
 * every applicant to a "Restricted" program benefits from, not an
 * admin-managed resource.
 */
class AdmissionTestGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Admission Tests';

    protected static ?string $title = 'Admission Tests (TOLC/IMAT)';

    protected static ?string $slug = 'admission-tests';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'Guides';

    protected static string $view = 'filament.pages.admission-test-guide';

    public function getSections(): array
    {
        return AdmissionTestCopy::SECTIONS;
    }

    public function getOfficialLinks(): array
    {
        return AdmissionTestCopy::OFFICIAL_LINKS;
    }
}
