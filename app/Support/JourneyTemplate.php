<?php

namespace App\Support;

/**
 * The end-to-end admission journey, as an ordered checklist. This is
 * curated national process (like App\Support\VisaArrivalCopy) — not
 * per-university data — rendered as tickable steps on App\Filament\Pages\MyJourney
 * and stored per student in App\Models\JourneyProgress.
 *
 * Each step declares `applies_when`: a list of rule tokens. A step is shown
 * when ANY of its tokens is active for the student (see activeTokens()).
 * `['always']` shows unconditionally.
 *
 * Tokens:
 *   always              — every applicant
 *   non_eu              — student is not an EU/EEA/Swiss citizen
 *   restricted_program  — a shortlisted program has numero programmato admission
 *   italian_taught      — a shortlisted program is taught in Italian
 *   wants_scholarship   — student flagged scholarship interest
 */
final class JourneyTemplate
{
    /** phase key => label, in order. */
    public const PHASES = [
        'research' => 'Research & shortlist',
        'prepare' => 'Prepare your documents',
        'apply' => 'Apply',
        'post_admission' => 'After you are admitted',
        'arrival' => 'Arrival in Italy',
    ];

    /**
     * @var array<int, array{key: string, phase: string, title: string, body: string, icon: string, help_route: ?string, applies_when: array<int, string>}>
     */
    public const STEPS = [
        [
            'key' => 'choose_subject',
            'phase' => 'research',
            'title' => 'Decide your subject and degree level',
            'body' => 'Settle on the field and whether you are applying for a Bachelor (laurea triennale) or a Master (laurea magistrale). Everything else follows from this.',
            'icon' => 'heroicon-o-academic-cap',
            'help_route' => null,
            'applies_when' => ['always'],
        ],
        [
            'key' => 'shortlist_programs',
            'phase' => 'research',
            'title' => 'Shortlist programs and note open vs restricted access',
            'body' => 'Save programs to My Applications. For each, check whether admission is open (libero accesso) or restricted (numero programmato) — it changes the whole process.',
            'icon' => 'heroicon-o-bookmark',
            'help_route' => 'filament.admin.pages.find-universities',
            'applies_when' => ['always'],
        ],
        [
            'key' => 'check_language_requirement',
            'phase' => 'research',
            'title' => 'Check each program’s language requirement',
            'body' => 'English-taught programs usually want IELTS 6.0–6.5 / TOEFL 80–90; Italian-taught programs want roughly B1–B2 Italian. Confirm the exact requirement on each program page.',
            'icon' => 'heroicon-o-language',
            'help_route' => null,
            'applies_when' => ['always'],
        ],
        [
            'key' => 'recognise_qualification',
            'phase' => 'prepare',
            'title' => 'Start qualification recognition (Dichiarazione di Valore / CIMEA)',
            'body' => 'Non-EU qualifications must be validated for admission. Begin the DoV or a CIMEA statement early — it is the slowest item in the whole process.',
            'icon' => 'heroicon-o-document-check',
            'help_route' => 'filament.admin.pages.doc-recognition',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'translate_documents',
            'phase' => 'prepare',
            'title' => 'Get your diploma and transcripts officially translated',
            'body' => 'Most universities require sworn/official translations of your prior diploma and academic transcript into Italian or English.',
            'icon' => 'heroicon-o-document-text',
            'help_route' => 'filament.admin.pages.doc-recognition',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'language_certificate',
            'phase' => 'prepare',
            'title' => 'Sit a language certificate exam',
            'body' => 'Book and take the English or Italian test your programs accept, leaving time for results to be issued before application deadlines.',
            'icon' => 'heroicon-o-pencil-square',
            'help_route' => null,
            'applies_when' => ['always'],
        ],
        [
            'key' => 'italian_language_plan',
            'phase' => 'prepare',
            'title' => 'Plan your Italian for an Italian-taught program',
            'body' => 'If you will study in Italian, check whether the university runs a pre-enrolment Italian course or placement test, and register for it.',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'help_route' => null,
            'applies_when' => ['italian_taught'],
        ],
        [
            'key' => 'register_admission_test',
            'phase' => 'apply',
            'title' => 'Register for the admission test (TOLC / IMAT)',
            'body' => 'Restricted-access programs require a standardized test. Create your account, pick a sitting date and register well before it closes.',
            'icon' => 'heroicon-o-clipboard-document-list',
            'help_route' => 'filament.admin.pages.admission-tests',
            'applies_when' => ['restricted_program'],
        ],
        [
            'key' => 'pre_enrol_universitaly',
            'phase' => 'apply',
            'title' => 'Pre-enrol on Universitaly',
            'body' => 'Non-EU applicants must complete pre-enrolment (preiscrizione) on universitaly.it before a visa can be issued. Start the moment the portal opens for your intake.',
            'icon' => 'heroicon-o-globe-europe-africa',
            'help_route' => 'filament.admin.pages.visa-arrival',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'submit_applications',
            'phase' => 'apply',
            'title' => 'Submit every university application before its deadline',
            'body' => 'Each university has its own portal and deadline. Track them in My Applications and submit with margin to spare.',
            'icon' => 'heroicon-o-paper-airplane',
            'help_route' => 'filament.admin.pages.my-applications',
            'applies_when' => ['always'],
        ],
        [
            'key' => 'apply_scholarships',
            'phase' => 'apply',
            'title' => 'Apply for regional / DSU scholarships',
            'body' => 'Right-to-study (diritto allo studio) benefits are run per region and have their own early deadlines — usually independent of your admission result.',
            'icon' => 'heroicon-o-banknotes',
            'help_route' => 'filament.admin.pages.my-scholarships',
            'applies_when' => ['wants_scholarship'],
        ],
        [
            'key' => 'admission_letter',
            'phase' => 'post_admission',
            'title' => 'Collect your official admission / acceptance letter',
            'body' => 'You need the university’s formal admission letter for the visa application — save a copy in My Documents.',
            'icon' => 'heroicon-o-envelope',
            'help_route' => 'filament.admin.pages.my-documents',
            'applies_when' => ['always'],
        ],
        [
            'key' => 'apply_visa',
            'phase' => 'post_admission',
            'title' => 'Apply for the Type D student visa',
            'body' => 'Book your consulate appointment as soon as pre-enrolment is validated — visa processing time is the main risk to making your start date.',
            'icon' => 'heroicon-o-identification',
            'help_route' => 'filament.admin.pages.visa-arrival',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'insurance_and_funds',
            'phase' => 'post_admission',
            'title' => 'Arrange health insurance and proof of funds',
            'body' => 'The consulate requires health cover valid in Italy and evidence you can support yourself. Get both in place before your visa appointment.',
            'icon' => 'heroicon-o-heart',
            'help_route' => 'filament.admin.pages.visa-arrival',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'permesso_di_soggiorno',
            'phase' => 'arrival',
            'title' => 'Apply for your permesso di soggiorno within 8 working days',
            'body' => 'Submit the residence-permit kit at a Poste Italiane Sportello Amico within 8 working days of arriving in Italy.',
            'icon' => 'heroicon-o-document-duplicate',
            'help_route' => 'filament.admin.pages.visa-arrival',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'codice_fiscale',
            'phase' => 'arrival',
            'title' => 'Get your codice fiscale',
            'body' => 'You need an Italian tax code to sign a lease, open a bank account and register with the health service. Universitaly often issues one during pre-enrolment — check first.',
            'icon' => 'heroicon-o-hashtag',
            'help_route' => 'filament.admin.pages.visa-arrival',
            'applies_when' => ['non_eu'],
        ],
        [
            'key' => 'enrolment',
            'phase' => 'arrival',
            'title' => 'Complete matriculation / enrolment at your university',
            'body' => 'Finalise immatricolazione: pay the first tuition instalment, submit your recognised documents and collect your student number.',
            'icon' => 'heroicon-o-check-badge',
            'help_route' => null,
            'applies_when' => ['always'],
        ],
    ];

    /**
     * Which rule tokens are active for this student right now.
     *
     * @param  bool  $hasRestrictedProgram  any shortlisted program is numero programmato
     * @param  bool  $hasItalianTaughtProgram  any shortlisted program is taught in Italian
     * @return array<int, string>
     */
    public static function activeTokens(
        bool $isEuCitizen,
        bool $wantsScholarship,
        bool $hasRestrictedProgram,
        bool $hasItalianTaughtProgram,
    ): array {
        $tokens = ['always'];

        if (! $isEuCitizen) {
            $tokens[] = 'non_eu';
        }

        if ($wantsScholarship) {
            $tokens[] = 'wants_scholarship';
        }

        if ($hasRestrictedProgram) {
            $tokens[] = 'restricted_program';
        }

        if ($hasItalianTaughtProgram) {
            $tokens[] = 'italian_taught';
        }

        return $tokens;
    }

    /**
     * Steps that apply given a set of active tokens, in template order.
     *
     * @param  array<int, string>  $tokens
     * @return array<int, array<string, mixed>>
     */
    public static function stepsForTokens(array $tokens): array
    {
        return array_values(array_filter(
            self::STEPS,
            fn (array $step) => array_intersect($step['applies_when'], $tokens) !== [],
        ));
    }
}
