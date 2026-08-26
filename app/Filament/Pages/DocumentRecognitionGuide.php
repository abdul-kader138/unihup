<?php

namespace App\Filament\Pages;

use App\Support\DocumentRecognitionCopy;
use Filament\Pages\Page;

/**
 * Standalone explainer for Dichiarazione di Valore (DOV) and CIMEA
 * statements — content lives in App\Support\DocumentRecognitionCopy since
 * this is uniform national policy, not per-university data (see that
 * class's doc comment). Open to every registered user, same as
 * FindUniversities — no HasPageShield/canAccess override, since this is
 * informational content every applicant benefits from, not an
 * admin-managed resource.
 */
class DocumentRecognitionGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Doc Recognition';

    protected static ?string $title = 'Doc Recognition (DOV/CIMEA)';

    protected static ?string $slug = 'doc-recognition';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.document-recognition-guide';

    public function getSections(): array
    {
        return DocumentRecognitionCopy::SECTIONS;
    }

    public function getOfficialLinks(): array
    {
        return DocumentRecognitionCopy::OFFICIAL_LINKS;
    }
}
