<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\UniversityRanking;
use Illuminate\Database\Seeder;

/**
 * CENSIS's annual "Classifica Censis delle Università italiane" — Italy's
 * most-cited domestic university ranking. Transcribed directly from the
 * official 2025/2026 edition PDF (see SOURCE_URL below; read in full while
 * building this feature, not summarized from a third party) — no CSV/API
 * exists for it, so unlike MurUstatImporter this can't be automated: CENSIS
 * publishes a new PDF once a year, and refreshing this means re-reading
 * that PDF and updating the constants below, the same way this file was
 * built.
 *
 * CENSIS ranks state and private universities in entirely separate tables,
 * each banded by enrollment size (see UniversityRanking::CATEGORIES) — a
 * "position" is only meaningful within its own category, not a national
 * rank. Non-state universities' methodology also excludes the
 * employability score entirely (see the PDF's own methodology note), which
 * is why those rows have a null 6th score.
 *
 * Matched to University rows by slug, transcribed from a live query
 * against this app's own database rather than guessed — CENSIS's short
 * institutional names ("Roma La Sapienza", "Milano Bicocca") don't match
 * this app's full names, so a name-similarity match would be unreliable
 * here; a row whose slug isn't found is skipped, not silently dropped.
 */
class UniversityRankingSeeder extends Seeder
{
    private const EDITION = '2025/2026';

    private const SOURCE_URL = 'https://www.censis.it/wp-content/uploads/2025/11/Classifica-Censis-delle-Universita-Italiane-2025-2026.pdf';

    /**
     * [slug, category, position, services, scholarships, facilities, communication_digital, internationalization, employability|null, overall]
     */
    private const RANKINGS = [
        // Mega atenei statali (oltre 40.000 iscritti)
        ['universita-degli-studi-di-padova', 'mega_statali', 1, 77, 83, 86, 110, 92, 94, 90.3],
        ['universita-degli-studi-di-bologna', 'mega_statali', 2, 72, 81, 88, 97, 96, 92, 87.7],
        ['universita-di-pisa', 'mega_statali', 3, 88, 75, 75, 97, 76, 97, 84.7],
        ['universita-degli-studi-di-roma-la-sapienza', 'mega_statali', 4, 70, 100, 79, 87, 80, 89, 84.2],
        ['universita-degli-studi-di-milano', 'mega_statali', 4, 75, 69, 82, 99, 83, 97, 84.2],
        ['universita-degli-studi-di-firenze', 'mega_statali', 5, 80, 67, 83, 97, 81, 93, 83.5],
        ['universita-degli-studi-di-torino', 'mega_statali', 6, 72, 74, 86, 95, 80, 91, 83.0],
        ['universita-degli-studi-di-palermo', 'mega_statali', 7, 73, 82, 87, 99, 80, 73, 82.3],
        ['universita-degli-studi-di-bari', 'mega_statali', 8, 77, 79, 83, 71, 67, 77, 75.7],
        ['universita-degli-studi-di-napoli-federico-ii', 'mega_statali', 9, 70, 93, 66, 66, 73, 85, 75.5],

        // Grandi atenei statali (20.000-40.000)
        ['universita-della-calabria', 'grandi_statali', 1, 110, 110, 89, 102, 79, 76, 94.3],
        ['universita-degli-studi-di-pavia', 'grandi_statali', 2, 78, 80, 97, 102, 90, 94, 90.2],
        ['universita-degli-studi-di-perugia', 'grandi_statali', 3, 76, 85, 90, 108, 89, 88, 89.3],
        ['universita-degli-studi-di-parma', 'grandi_statali', 4, 72, 77, 100, 108, 82, 94, 88.8],
        ['universita-degli-studi-di-cagliari', 'grandi_statali', 5, 81, 101, 86, 97, 76, 84, 87.5],
        ['universita-degli-studi-di-salerno', 'grandi_statali', 6, 75, 95, 94, 103, 70, 80, 86.2],
        ['universita-degli-studi-di-milano-bicocca', 'grandi_statali', 7, 71, 79, 87, 95, 77, 103, 85.3],
        ['universita-degli-studi-di-genova', 'grandi_statali', 8, 73, 67, 88, 100, 82, 99, 84.8],
        ['universita-degli-studi-di-roma-tor-vergata', 'grandi_statali', 8, 74, 79, 90, 86, 83, 97, 84.8],
        ['universita-degli-studi-di-modena-e-reggio-emilia', 'grandi_statali', 9, 72, 69, 90, 90, 81, 104, 84.3],
        ['universita-degli-studi-di-verona', 'grandi_statali', 10, 70, 69, 88, 92, 78, 101, 83.0],
        ['universita-degli-studi-roma-tre', 'grandi_statali', 11, 70, 69, 95, 97, 78, 87, 82.7],
        ['universita-degli-studi-di-ferrara', 'grandi_statali', 12, 71, 73, 88, 79, 74, 101, 81.0],
        ['universita-degli-studi-di-catania', 'grandi_statali', 13, 72, 71, 86, 105, 71, 79, 80.7],
        ['universita-degli-studi-gabriele-dannunzio-di-chieti-e-pescara', 'grandi_statali', 14, 76, 75, 94, 87, 72, 76, 80.0],
        ['universita-degli-studi-di-messina', 'grandi_statali', 15, 73, 81, 78, 105, 72, 67, 79.3],
        ['universita-degli-studi-della-campania-luigi-vanvitelli', 'grandi_statali', 16, 72, 90, 84, 79, 69, 78, 78.7],

        // Medi atenei statali (10.000-20.000)
        ['universita-degli-studi-di-trento', 'medi_statali', 1, 77, 81, 101, 95, 110, 98, 93.7],
        ['universita-degli-studi-di-udine', 'medi_statali', 2, 84, 81, 96, 110, 80, 102, 92.2],
        ['universita-politecnica-delle-marche-ancona', 'medi_statali', 2, 80, 84, 105, 99, 79, 106, 92.2],
        ['universita-degli-studi-di-siena', 'medi_statali', 3, 91, 79, 101, 92, 90, 85, 89.7],
        ['universita-degli-studi-di-sassari', 'medi_statali', 4, 84, 92, 110, 79, 84, 84, 88.8],
        ['universita-degli-studi-di-trieste', 'medi_statali', 5, 84, 76, 95, 94, 81, 102, 88.7],
        ['universita-ca-foscari-venezia', 'medi_statali', 6, 77, 70, 81, 105, 101, 94, 88.0],
        ['universita-degli-studi-del-piemonte-orientale-amedeo-avogadro', 'medi_statali', 7, 68, 69, 105, 107, 75, 103, 87.8],
        ['universita-degli-studi-di-brescia', 'medi_statali', 8, 88, 68, 88, 99, 77, 104, 87.3],
        ['universita-degli-studi-di-bergamo', 'medi_statali', 9, 77, 66, 92, 94, 85, 103, 86.2],
        ['universita-degli-studi-carlo-bo-di-urbino', 'medi_statali', 10, 86, 78, 87, 95, 71, 87, 84.0],
        ['universita-degli-studi-dell-insubria', 'medi_statali', 11, 80, 66, 76, 92, 85, 104, 83.8],
        ['universita-degli-studi-di-napoli-parthenope', 'medi_statali', 12, 79, 96, 94, 76, 73, 84, 83.7],
        ['universita-del-salento', 'medi_statali', 13, 94, 89, 94, 86, 72, 66, 83.5],
        ['universita-degli-studi-di-foggia', 'medi_statali', 14, 73, 82, 84, 94, 84, 78, 82.5],
        ['universita-degli-studi-di-laquila', 'medi_statali', 15, 72, 66, 79, 99, 80, 96, 82.0],
        ['universita-degli-studi-di-catanzaro-magna-grecia', 'medi_statali', 16, 77, 100, 84, 69, 66, 83, 79.8],
        ['universita-degli-studi-lorientale-di-napoli', 'medi_statali', 17, 70, 92, 78, 94, 75, 66, 79.2],

        // Piccoli atenei statali (fino a 10.000)
        ['universita-degli-studi-di-camerino', 'piccoli_statali', 1, 92, 87, 103, 105, 93, 96, 96.0],
        ['universita-degli-studi-di-cassino-e-del-lazio-meridionale', 'piccoli_statali', 2, 70, 110, 89, 99, 89, 77, 89.0],
        ['universita-degli-studi-della-tuscia', 'piccoli_statali', 3, 71, 88, 98, 103, 86, 84, 88.3],
        ['universita-degli-studi-di-macerata', 'piccoli_statali', 4, 93, 78, 101, 89, 79, 80, 86.7],
        ['universita-degli-studi-del-sannio', 'piccoli_statali', 5, 82, 84, 92, 100, 77, 74, 84.8],
        ['universita-degli-studi-mediterranea-di-reggio-calabria', 'piccoli_statali', 6, 79, 110, 100, 71, 77, 69, 84.3],
        ['universita-degli-studi-della-basilicata', 'piccoli_statali', 7, 75, 77, 98, 95, 67, 83, 82.5],
        ['universita-degli-studi-di-teramo', 'piccoli_statali', 8, 68, 74, 102, 95, 76, 73, 81.3],
        ['universita-degli-studi-del-molise', 'piccoli_statali', 9, 66, 76, 95, 86, 68, 83, 79.0],

        // Politecnici
        ['politecnico-di-milano', 'politecnici', 1, 83, 87, 103, 105, 105, 110, 98.8],
        ['politecnico-di-torino', 'politecnici', 2, 72, 90, 85, 97, 101, 110, 92.5],
        ['universita-iuav-di-venezia', 'politecnici', 3, 73, 73, 82, 92, 103, 97, 86.7],
        ['politecnico-di-bari', 'politecnici', 4, 77, 83, 80, 86, 76, 109, 85.2],

        // Grandi atenei non statali (oltre 10.000) — no employability score in CENSIS's methodology for this table
        ['luiss-libera-universita-internazionale-degli-studi-sociali-guido-carli-di-roma', 'grandi_non_statali', 1, 75, 110, 83, 99, 104, null, 94.2],
        ['universita-commerciale-luigi-bocconi-di-milano', 'grandi_non_statali', 2, 77, 101, 66, 103, 110, null, 91.4],
        ['universita-cattolica-del-sacro-cuore', 'grandi_non_statali', 3, 80, 69, 67, 94, 80, null, 78.0],

        // Medi atenei non statali (5.000-10.000)
        ['libera-universita-maria-ssassunta-lumsa-di-roma', 'medi_non_statali', 1, 73, 78, 83, 103, 78, null, 83.0],
        ['libera-universita-di-lingue-e-comunicazione-iulm', 'medi_non_statali', 2, 74, 70, 100, 74, 80, null, 79.6],
        ['universita-degli-studi-suor-orsola-benincasa-di-napoli', 'medi_non_statali', 3, 80, 82, 77, 70, 67, null, 75.2],

        // Piccoli atenei non statali (fino a 5.000)
        ['libera-universita-di-bolzano', 'piccoli_non_statali', 1, 110, 73, 110, 95, 88, null, 95.2],
        ['universita-europea-di-roma', 'piccoli_non_statali', 2, 73, 74, 96, 109, 83, null, 87.0],
        ['universita-campus-bio-medico-di-roma', 'piccoli_non_statali', 3, 107, 81, 83, 92, 71, null, 86.8],
        ['universita-degli-studi-internazionali-di-roma-unint', 'piccoli_non_statali', 4, 69, 76, 94, 110, 84, null, 86.6],
        ['universita-carlo-cattaneo-liuc', 'piccoli_non_statali', 5, 69, 81, 89, 85, 99, null, 84.6],
        ['link-campus-university-di-roma', 'piccoli_non_statali', 6, 66, 87, 107, 66, 78, null, 80.8],
        ['libera-universita-della-sicilia-centrale-kore-di-enna', 'piccoli_non_statali', 7, 70, 73, 101, 89, 66, null, 79.8],
        ['libera-universita-mediterranea-giuseppe-degennaro', 'piccoli_non_statali', 8, 84, 76, 88, 72, 72, null, 78.4],
        ['libera-universita-vita-salute-san-raffaele-di-milano', 'piccoli_non_statali', 9, 69, 66, 80, 77, 73, null, 73.0],
        ['universita-della-valle-daosta', 'piccoli_non_statali', 10, 66, 70, 69, 70, 89, null, 72.8],
    ];

    public function run(): void
    {
        $universityIds = University::whereIn('slug', array_column(self::RANKINGS, 0))->pluck('id', 'slug');

        foreach (self::RANKINGS as $row) {
            [$slug, $category, $position, $services, $scholarships, $facilities, $commsDigital, $intl, $employability, $overall] = $row;

            $universityId = $universityIds[$slug] ?? null;
            if (! $universityId) {
                continue;
            }

            UniversityRanking::updateOrCreate(
                ['university_id' => $universityId, 'edition' => self::EDITION],
                [
                    'category' => $category,
                    'position' => $position,
                    'score_services' => $services,
                    'score_scholarships' => $scholarships,
                    'score_facilities' => $facilities,
                    'score_communication_digital' => $commsDigital,
                    'score_internationalization' => $intl,
                    'score_employability' => $employability,
                    'overall_score' => $overall,
                    'source_url' => self::SOURCE_URL,
                    'last_verified_at' => now(),
                ],
            );
        }
    }
}
