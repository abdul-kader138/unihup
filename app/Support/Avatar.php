<?php

namespace App\Support;

/**
 * Deterministic "initials on a colored square" placeholder — same idea as
 * Slack/Google Workspace's default avatars — shown wherever a University
 * has no real logo (which, before UniversityLogoEnricher runs, is all of
 * them). Pure SVG data URI, no network request or stored file involved.
 */
final class Avatar
{
    private const PALETTE = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#dc2626', '#0891b2', '#4f46e5'];

    private const STOPWORDS = [
        'università', 'university', 'degli', 'studi', 'di', 'del', 'della', 'delle', 'dei',
        'libera', 'ente', 'la', 'le', 'il', 'of', 'the', 'per', 'statale', 'autonoma', 'regionale',
    ];

    public static function initialsDataUri(string $name): string
    {
        $initials = e(self::initials($name));
        $color = self::colorFor($name);

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" rx="14" fill="{$color}"/>
                <text x="32" y="41" font-family="system-ui, -apple-system, sans-serif" font-size="24" font-weight="600" fill="white" text-anchor="middle">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    private static function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim(str_replace("'", ' ', $name))) ?: [];

        $meaningful = array_values(array_filter(
            $words,
            fn (string $word) => $word !== '' && ! in_array(mb_strtolower($word), self::STOPWORDS, true)
        ));

        $chosen = array_slice($meaningful === [] ? $words : $meaningful, 0, 2);

        $letters = array_map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)), $chosen);

        return implode('', $letters) ?: '?';
    }

    private static function colorFor(string $name): string
    {
        return self::PALETTE[crc32($name) % count(self::PALETTE)];
    }
}
