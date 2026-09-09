<?php

namespace App\Support;

/**
 * Shared CEFR-style language level scale used by the student study profile
 * and App\Support\EligibilityEngine. Keys are stored on users.english_level
 * / users.italian_level.
 */
final class ProficiencyLevels
{
    /** key => label, weakest to strongest. */
    public const LEVELS = [
        'none' => 'None / just starting',
        'a1' => 'A1 — Beginner',
        'a2' => 'A2 — Elementary',
        'b1' => 'B1 — Intermediate',
        'b2' => 'B2 — Upper intermediate',
        'c1' => 'C1 — Advanced',
        'c2' => 'C2 — Proficient',
        'native' => 'Native / fluent',
    ];

    /** Numeric rank for comparisons (0 = none … 7 = native). */
    private const RANK = [
        'none' => 0, 'a1' => 1, 'a2' => 2, 'b1' => 3,
        'b2' => 4, 'c1' => 5, 'c2' => 6, 'native' => 7,
    ];

    public static function options(): array
    {
        return self::LEVELS;
    }

    public static function rank(?string $level): int
    {
        return self::RANK[$level] ?? 0;
    }

    /** True when $level is at or above $minimum on the scale. */
    public static function atLeast(?string $level, string $minimum): bool
    {
        return $level !== null && self::rank($level) >= self::rank($minimum);
    }

    public static function label(?string $level): ?string
    {
        return $level ? (self::LEVELS[$level] ?? $level) : null;
    }
}
