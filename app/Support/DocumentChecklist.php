<?php

namespace App\Support;

/**
 * The canonical set of documents an international applicant to an Italian
 * university typically needs, in roughly the order they come up. Drives the
 * type dropdown and the phase grouping in the student document vault
 * (App\Filament\Pages\MyDocuments / App\Models\StudentDocument).
 *
 * This is a curated national checklist, not per-university data — a specific
 * program may ask for more or fewer. `help_route` points at the in-app guide
 * that explains how to obtain that document; `help_url` is an external
 * official source when there is no in-app guide.
 *
 * @phpstan-type ChecklistEntry array{label: string, description: string, phase: string, help_route?: string, help_url?: string}
 */
final class DocumentChecklist
{
    /** Phase key => display label, in journey order. */
    public const PHASES = [
        'preparation' => 'Before you apply',
        'application' => 'Applying',
        'post_admission' => 'After admission',
    ];

    /**
     * Type key => entry. Keep keys stable — they are stored in
     * student_documents.type.
     *
     * @var array<string, array<string, string>>
     */
    public const TYPES = [
        'passport' => [
            'label' => 'Passport',
            'description' => 'Valid for the whole period of study, ideally 6+ months beyond your planned arrival.',
            'phase' => 'preparation',
        ],
        'photo' => [
            'label' => 'Passport photo',
            'description' => 'Recent ID-style photo — needed for the visa application and the residence permit kit.',
            'phase' => 'preparation',
        ],
        'prior_diploma' => [
            'label' => 'Prior qualification / diploma',
            'description' => 'Your final secondary-school diploma (for a Bachelor) or Bachelor degree certificate (for a Master), with official translation.',
            'phase' => 'preparation',
            'help_route' => 'filament.admin.pages.doc-recognition',
        ],
        'transcript' => [
            'label' => 'Academic transcript',
            'description' => 'List of subjects and grades from your prior studies, officially translated.',
            'phase' => 'preparation',
            'help_route' => 'filament.admin.pages.doc-recognition',
        ],
        'dov' => [
            'label' => 'Dichiarazione di Valore (DoV)',
            'description' => 'Statement of value from the Italian diplomatic mission in your country — or its CIMEA equivalent.',
            'phase' => 'preparation',
            'help_route' => 'filament.admin.pages.doc-recognition',
        ],
        'cimea_statement' => [
            'label' => 'CIMEA statement (Comparability / Verification)',
            'description' => 'Digital alternative to the DoV accepted by many universities — issued via the Diplome portal.',
            'phase' => 'preparation',
            'help_route' => 'filament.admin.pages.doc-recognition',
        ],
        'language_certificate' => [
            'label' => 'Language certificate',
            'description' => 'English (IELTS/TOEFL/...) or Italian (CILS/CELI/PLIDA) proof at the level the program requires.',
            'phase' => 'preparation',
        ],
        'cv' => [
            'label' => 'CV / résumé',
            'description' => 'Europass or plain format — required by most Master applications.',
            'phase' => 'application',
        ],
        'motivation_letter' => [
            'label' => 'Motivation letter',
            'description' => 'Statement of purpose tailored to each program you apply to.',
            'phase' => 'application',
        ],
        'recommendation_letter' => [
            'label' => 'Recommendation letter',
            'description' => 'One or two academic/professional references, if the program asks for them.',
            'phase' => 'application',
        ],
        'pre_enrolment_confirmation' => [
            'label' => 'Pre-enrolment confirmation (Universitaly)',
            'description' => 'The receipt from universitaly.it once you submit your pre-enrolment application for the visa.',
            'phase' => 'application',
            'help_route' => 'filament.admin.pages.visa-arrival',
        ],
        'admission_letter' => [
            'label' => 'Admission / acceptance letter',
            'description' => 'Official letter from the university confirming your place — needed for the visa.',
            'phase' => 'post_admission',
        ],
        'financial_proof' => [
            'label' => 'Proof of financial means',
            'description' => 'Bank statement or scholarship award meeting the minimum the consulate requires for the study visa.',
            'phase' => 'post_admission',
            'help_route' => 'filament.admin.pages.visa-arrival',
        ],
        'health_insurance' => [
            'label' => 'Health insurance',
            'description' => 'Travel/health cover valid in Italy for the visa, later converted to SSN registration.',
            'phase' => 'post_admission',
            'help_route' => 'filament.admin.pages.visa-arrival',
        ],
        'visa_d' => [
            'label' => 'Type D student visa',
            'description' => 'The national long-stay visa issued by the Italian consulate — scan it once you have it.',
            'phase' => 'post_admission',
            'help_route' => 'filament.admin.pages.visa-arrival',
        ],
        'other' => [
            'label' => 'Other document',
            'description' => 'Anything else a specific program or consulate asks you for.',
            'phase' => 'application',
        ],
    ];

    /** @return array<string, string> type key => label, for a Filament select */
    public static function options(): array
    {
        return array_map(fn (array $entry) => $entry['label'], self::TYPES);
    }

    /** @return array<string, string> type key => label, grouped by phase label */
    public static function groupedOptions(): array
    {
        $grouped = [];

        foreach (self::PHASES as $phaseKey => $phaseLabel) {
            foreach (self::TYPES as $key => $entry) {
                if (($entry['phase'] ?? null) === $phaseKey) {
                    $grouped[$phaseLabel][$key] = $entry['label'];
                }
            }
        }

        return $grouped;
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function helpUrl(string $type): ?string
    {
        $entry = self::TYPES[$type] ?? null;

        if ($entry === null) {
            return null;
        }

        if (isset($entry['help_route'])) {
            return route($entry['help_route']);
        }

        return $entry['help_url'] ?? null;
    }
}
