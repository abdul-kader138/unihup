<?php

namespace App\Support;

use App\Models\ShortlistItem;

/**
 * The per-program application to-do list — distinct from the overall
 * App\Support\JourneyTemplate (which is the whole journey, once). This one
 * runs once per saved program and tracks the concrete submission steps.
 * Stored in App\Models\ApplicationProgress, shown on My Applications.
 *
 * `applies_when` tokens: always | non_eu | restricted
 */
final class ApplicationSteps
{
    /**
     * @var array<int, array{key: string, label: string, hint: string, applies_when: string}>
     */
    public const STEPS = [
        ['key' => 'portal_account', 'label' => 'Create an account on the university\'s application portal', 'hint' => 'Each university runs its own portal — find the link on the program\'s official admission page.', 'applies_when' => 'always'],
        ['key' => 'pre_enrolment', 'label' => 'Complete pre-enrolment on Universitaly', 'hint' => 'Required before a visa can be issued. Do it as soon as the portal opens for your intake.', 'applies_when' => 'non_eu'],
        ['key' => 'test_registered', 'label' => 'Register for the admission test (TOLC / IMAT)', 'hint' => 'Restricted-access programs only. Register well before the sitting closes.', 'applies_when' => 'restricted'],
        ['key' => 'documents_uploaded', 'label' => 'Upload every required document', 'hint' => 'Transcript, diploma, DoV/CIMEA, language certificate, CV, motivation letter — check the bando for the exact list.', 'applies_when' => 'always'],
        ['key' => 'fee_paid', 'label' => 'Pay the application fee', 'hint' => 'Many Italian universities charge €30–100 per application.', 'applies_when' => 'always'],
        ['key' => 'submitted', 'label' => 'Submit the application before the deadline', 'hint' => 'Submit with margin to spare — portals get slow near the cut-off.', 'applies_when' => 'always'],
        ['key' => 'result', 'label' => 'Receive the admission result', 'hint' => 'Restricted programs publish a ranked list; open-access ones confirm eligibility.', 'applies_when' => 'always'],
        ['key' => 'offer_accepted', 'label' => 'Accept the offer / pay the deposit', 'hint' => 'Confirm your place and pay the first tuition instalment to hold it.', 'applies_when' => 'always'],
    ];

    /**
     * Steps that apply to a given saved program + student situation.
     *
     * @return array<int, array<string, string>>
     */
    public static function forItem(ShortlistItem $item, bool $isEuCitizen): array
    {
        $restricted = $item->degreeProgram?->admission_type === 'restricted';

        return array_values(array_filter(self::STEPS, function (array $step) use ($restricted, $isEuCitizen) {
            return match ($step['applies_when']) {
                'non_eu' => ! $isEuCitizen,
                'restricted' => $restricted,
                default => true,
            };
        }));
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_column(self::STEPS, 'key');
    }

    public static function label(string $key): string
    {
        foreach (self::STEPS as $step) {
            if ($step['key'] === $key) {
                return $step['label'];
            }
        }

        return $key;
    }
}
