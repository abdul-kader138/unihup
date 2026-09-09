<?php

namespace App\Support;

use App\Models\DegreeProgram;
use App\Models\User;

/**
 * A soft "can I apply?" read on a degree program for a given student, from
 * the study-profile fields and the program's language + admission type.
 * Deliberately conservative: it flags things to check, it does not promise
 * admission, and it only returns `ineligible` on a hard language-floor
 * mismatch. Same "general guidance, not per-program fact" framing as
 * App\Support\LanguageProficiencyCopy.
 */
final class EligibilityEngine
{
    public const ELIGIBLE = 'eligible';

    public const CHECK = 'check';

    public const INELIGIBLE = 'ineligible';

    public const LABELS = [
        self::ELIGIBLE => 'Likely eligible',
        self::CHECK => 'Check requirements',
        self::INELIGIBLE => 'Below typical minimum',
    ];

    public const COLORS = [
        self::ELIGIBLE => 'success',
        self::CHECK => 'warning',
        self::INELIGIBLE => 'danger',
    ];

    /**
     * @return array{verdict: string, reasons: array<int, string>}
     */
    public static function assess(DegreeProgram $program, User $user): array
    {
        if (! $user->hasCompletedStudyProfile()) {
            return [
                'verdict' => self::CHECK,
                'reasons' => ['Complete your study profile for a personalised check.'],
            ];
        }

        $reasons = [];
        $verdict = self::ELIGIBLE;

        $isItalian = $program->language === 'Italian';
        $isEnglish = $program->language === 'English';
        $level = $isItalian ? $user->italian_level : ($isEnglish ? $user->english_level : null);

        if ($isItalian || $isEnglish) {
            $languageName = $isItalian ? 'Italian' : 'English';

            if (ProficiencyLevels::rank($level) <= ProficiencyLevels::rank('a1')) {
                $verdict = self::INELIGIBLE;
                $reasons[] = "Your recorded {$languageName} level is below the typical minimum for a {$languageName}-taught program.";
            } elseif (! ProficiencyLevels::atLeast($level, $isItalian ? 'b1' : 'b2')) {
                $verdict = self::worst($verdict, self::CHECK);
                $reasons[] = "This program is taught in {$languageName}; most ask for at least ".
                    ($isItalian ? 'B1–B2 Italian' : 'B2 English (IELTS ~6.0–6.5)').'.';
            }
        } else {
            $reasons[] = "Confirm the {$program->language} language requirement on the program page.";
            $verdict = self::worst($verdict, self::CHECK);
        }

        if ($program->degree_level === 'master') {
            $reasons[] = "Master's admission requires a completed Bachelor's degree in a related field.";
        }

        if ($program->admission_type === 'restricted') {
            $verdict = self::worst($verdict, self::CHECK);
            $reasons[] = 'Restricted access: you must sit an admission test (TOLC/IMAT) and rank within the available seats.';
        }

        if (! $user->is_eu_citizen) {
            $reasons[] = 'As a non-EU applicant you also need pre-enrolment on Universitaly and a Type D visa.';
        }

        if ($verdict === self::ELIGIBLE && $reasons === []) {
            $reasons[] = 'Your profile matches the typical entry requirements — always confirm on the official page.';
        }

        return ['verdict' => $verdict, 'reasons' => $reasons];
    }

    private static function worst(string $current, string $candidate): string
    {
        $order = [self::ELIGIBLE => 0, self::CHECK => 1, self::INELIGIBLE => 2];

        return $order[$candidate] > $order[$current] ? $candidate : $current;
    }
}
