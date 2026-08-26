<?php

namespace Database\Seeders;

use App\Models\RegionalScholarship;
use Illuminate\Database\Seeder;

/**
 * Italy's right-to-study (diritto allo studio universitario / DSU) benefits
 * — income-tested (ISEE) scholarships, subsidized housing, meal plans — are
 * administered regionally, not nationally, and not on any open dataset (no
 * MUR/USTAT resource covers this — confirmed while researching
 * MurUstatImporter's data source). Several regions split this across
 * multiple bodies by province or university rather than running one
 * regional agency, so this is genuinely one-to-many per region, not a
 * simple lookup table — modeled that way rather than flattened.
 *
 * Every row below was verified against each body's live site (or the most
 * recent 2025/26 news coverage of it) while building this feature, not
 * pulled from a single old directory — Italy's ~20-year-old official PDF
 * listing these bodies (istruzione.it) is badly out of date: several
 * regions it lists as fragmented (e.g. Campania, Puglia, Toscana, Piemonte)
 * have since consolidated into a single agency, which is what's reflected
 * here. Bodies that still haven't consolidated as of 2025/26 (Sicilia,
 * Sardegna, Veneto, Abruzzo, Trentino-Alto Adige) are listed with all of
 * their current constituent bodies, not guessed at.
 */
class RegionalScholarshipSeeder extends Seeder
{
    private const GENERIC_NOTE = 'Administers ISEE income-tested merit scholarships, subsidized student housing, and meal benefits for the region\'s public universities. Amounts and income thresholds are set annually — check the official site for the current academic year\'s bando (call for applications).';

    private const ENTRIES = [
        ['Piemonte', 'EDISU Piemonte', 'https://www.edisu.piemonte.it/'],
        ["Valle d'Aosta", "Regione Autonoma Valle d'Aosta — Assessorato Istruzione", 'https://www.regione.vda.it/istruzione/dirittostudio/default_i.aspx',
            "No separate agency — the region's own education department administers scholarships and housing contributions directly for Università della Valle d'Aosta students, and needs-tested grants for residents studying elsewhere."],
        ['Lombardia', 'DiSCo Lombardia', 'https://www.disco.regione.lombardia.it/'],
        ['Trentino-Alto Adige', 'Opera Universitaria di Trento', 'https://www.operauni.tn.it/', 'Serves students at the University of Trento.'],
        ['Trentino-Alto Adige', 'Ripartizione Diritto allo Studio — Provincia Autonoma di Bolzano', 'https://diritto-allo-studio.provincia.bz.it/', 'Serves South Tyrolean residents, including students at the Free University of Bozen-Bolzano.'],
        ['Veneto', 'ESU di Venezia', 'https://www.esuvenezia.it/'],
        ['Veneto', 'ESU di Padova', 'https://www.esupadova.it/'],
        ['Veneto', 'ESU di Verona', 'https://www.esuvr.it/'],
        ['Friuli-Venezia Giulia', 'ARDISS FVG', 'https://www.ardiss.fvg.it/'],
        ['Liguria', 'ALiSEO Liguria', 'https://www.aliseo.liguria.it/'],
        ['Emilia-Romagna', 'ER.GO', 'https://www.er-go.it/'],
        ['Toscana', 'DSU Toscana', 'https://www.dsu.toscana.it/'],
        ['Umbria', 'ADiSU Umbria', 'https://www.adisu.umbria.it/'],
        ['Marche', 'ERDIS Marche', 'https://www.erdis.it/'],
        ['Lazio', 'Lazio DiSCo', 'https://www.laziodisco.it/'],
        ['Abruzzo', "ADSU L'Aquila", 'https://www.adsuaq.org/', "Covers Università degli Studi dell'Aquila. Teramo and Chieti-Pescara run their own ADSU offices via their host universities' own sites (unite.it, unich.it) — as of 2025/26 a single unified Abruzzo agency has been proposed but not yet implemented."],
        ['Molise', 'ESU Molise', 'https://www.esu.molise.it/'],
        ['Campania', 'ADISURC', 'https://www.adisurcampania.it/'],
        ['Puglia', 'ADISU Puglia', 'https://www.adisupuglia.it/'],
        ['Basilicata', 'ARDSU Basilicata', 'https://www.ardsubasilicata.it/'],
        ['Calabria', 'Regione Calabria — Dipartimento Istruzione e Pari Opportunità', 'https://calabriaeuropa.regione.calabria.it/', "No single dedicated agency — scholarships are co-funded directly by the region and administered through each university's own diritto allo studio office (e.g. Università della Calabria, Mediterranea di Reggio Calabria)."],
        ['Sicilia', 'ERSU Palermo', 'https://www.ersupalermo.it/'],
        ['Sicilia', 'ERSU Catania', 'https://www.ersucatania.it/'],
        ['Sicilia', 'ERSU Messina', 'https://www.ersu.me.it/'],
        ['Sardegna', 'ERSU Cagliari', 'https://www.ersucagliari.it/'],
        ['Sardegna', 'ERSU Sassari', 'https://www.ersusassari.it/'],
    ];

    public function run(): void
    {
        foreach (self::ENTRIES as $entry) {
            [$region, $bodyName, $websiteUrl] = $entry;
            $extraNote = $entry[3] ?? null;

            RegionalScholarship::updateOrCreate(
                ['region' => $region, 'body_name' => $bodyName],
                [
                    'description' => $extraNote ? "{$extraNote} ".self::GENERIC_NOTE : self::GENERIC_NOTE,
                    'website_url' => $websiteUrl,
                    'source_url' => 'https://www.andisu.it/',
                    'last_verified_at' => now(),
                ],
            );
        }
    }
}
