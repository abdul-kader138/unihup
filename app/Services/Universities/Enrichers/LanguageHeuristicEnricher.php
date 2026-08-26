<?php

namespace App\Services\Universities\Enrichers;

use App\Contracts\DataEnricher;
use App\Contracts\EnrichmentResult;
use App\Models\DegreeProgram;
use Illuminate\Support\Str;

/**
 * Flags degree programs likely taught in English, based on their official
 * course title.
 *
 * No open dataset records language of instruction (see MurUstatImporter's
 * class doc comment for what was checked), so MUR-imported programs all
 * default to 'Italian'. This is a genuine heuristic, not a verified fact:
 * it only flips a program to 'English' when the title contains zero
 * Italian grammatical markers (di, e, della, degli, per, ...) — a very
 * reliable "this is Italian" signal when present — and does contain a
 * common English academic word. Ambiguous titles (e.g. a proper noun with
 * no function words either way) are deliberately left as 'Italian', the
 * statistically correct default, rather than guessed at. Treat the result
 * as "worth double-checking", not authoritative — a human should confirm
 * before advertising a program to English-speaking applicants.
 */
class LanguageHeuristicEnricher implements DataEnricher
{
    private const ITALIAN_MARKERS = [
        ' di ', ' e ', ' della ', ' delle ', ' degli ', ' del ', ' dei ',
        ' per ', ' con ', ' in ', ' allo ', ' alla ', ' nella ',
    ];

    private const ENGLISH_SIGNALS = [
        'science', 'management', 'engineering', 'studies', 'international',
        'global', 'data', 'business', 'design', 'and', 'of the', 'computer',
        'economics', 'digital', 'sustainable', 'innovation',
    ];

    public function enrich(): EnrichmentResult
    {
        $updated = 0;
        $skipped = 0;

        DegreeProgram::query()
            ->where('language', 'Italian')
            ->select(['id', 'name'])
            ->chunkById(500, function ($programs) use (&$updated, &$skipped) {
                foreach ($programs as $program) {
                    if ($this->looksEnglish($program->name)) {
                        $program->forceFill(['language' => 'English'])->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            });

        return new EnrichmentResult(
            updated: $updated,
            skipped: $skipped,
            summary: "Flagged {$updated} degree program(s) as likely English-taught based on their title (heuristic — verify before publishing).",
        );
    }

    private function looksEnglish(string $name): bool
    {
        $padded = ' '.Str::lower($name).' ';

        if (Str::contains($padded, self::ITALIAN_MARKERS) || preg_match('/[àèéìòù]/u', $padded)) {
            return false;
        }

        return Str::contains($padded, self::ENGLISH_SIGNALS);
    }
}
