<?php

namespace App\Services\Universities\Enrichers;

use App\Contracts\DataEnricher;
use App\Contracts\EnrichmentResult;
use App\Models\University;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fills University.website_url — a field neither MurUstatImporter's source
 * data nor SeedDataImporter's bundled seeders cover for every institution.
 *
 * No open dataset publishes official institutional domains, so this uses a
 * hand-curated map (domains I have reasonable confidence in from general
 * knowledge of Italian higher education). Every candidate is still verified
 * live with an HTTP request before being written — a wrong or stale entry
 * in the map fails the check and is skipped rather than silently stored,
 * and the response body must plausibly belong to a university (mentions
 * "università"/"university"/"politecnico"/"ateneo") so an unrelated site
 * that happens to respond 200 doesn't get accepted.
 *
 * Institutions with no map entry are left untouched — this class never
 * guesses a domain algorithmically, since an unverified guess risks
 * pointing students at the wrong site.
 */
class UniversityWebsiteEnricher implements DataEnricher
{
    /** slug => candidate official domain (verified before being stored) */
    private const KNOWN_DOMAINS = [
        'universita-degli-studi-di-torino' => 'unito.it',
        'politecnico-di-torino' => 'polito.it',
        'politecnico-di-milano' => 'polimi.it',
        'politecnico-di-bari' => 'poliba.it',
        'universita-degli-studi-di-milano' => 'unimi.it',
        'universita-degli-studi-di-milano-bicocca' => 'unimib.it',
        'universita-cattolica-del-sacro-cuore' => 'unicatt.it',
        'universita-commerciale-luigi-bocconi-di-milano' => 'unibocconi.it',
        'libera-universita-di-lingue-e-comunicazione-iulm' => 'iulm.it',
        'libera-universita-vita-salute-san-raffaele-di-milano' => 'unisr.it',
        'universita-carlo-cattaneo-liuc' => 'liuc.it',
        'universita-degli-studi-di-bergamo' => 'unibg.it',
        'universita-degli-studi-di-brescia' => 'unibs.it',
        'universita-degli-studi-di-pavia' => 'unipv.it',
        'universita-degli-studi-dell-insubria' => 'uninsubria.it',
        'universita-telematica-e-campus-di-novedrate-co' => 'uniecampus.it',
        'universita-telematica-pegaso-di-napoli' => 'unipegaso.it',
        'universita-telematica-guglielmo-marconi-di-roma' => 'unimarconi.it',
        'universita-telematica-niccolo-cusano-di-roma' => 'unicusano.it',
        'universita-telematica-unitelma-sapienza-di-roma' => 'unitelmasapienza.it',
        'universita-degli-studi-di-genova' => 'unige.it',
        'universita-della-valle-daosta' => 'univda.it',
        'universita-di-scienze-gastronomiche' => 'unisg.it',
        'universita-degli-studi-del-piemonte-orientale-amedeo-avogadro' => 'uniupo.it',
        'universita-degli-studi-di-napoli-federico-ii' => 'unina.it',
        'universita-degli-studi-di-napoli-parthenope' => 'uniparthenope.it',
        'universita-degli-studi-lorientale-di-napoli' => 'unior.it',
        'universita-degli-studi-della-campania-luigi-vanvitelli' => 'unicampania.it',
        'universita-degli-studi-di-salerno' => 'unisa.it',
        'universita-degli-studi-del-sannio' => 'unisannio.it',
        'universita-degli-studi-di-padova' => 'unipd.it',
        'universita-ca-foscari-venezia' => 'unive.it',
        'universita-iuav-di-venezia' => 'iuav.it',
        'universita-degli-studi-di-verona' => 'univr.it',
        'universita-degli-studi-di-trento' => 'unitn.it',
        'universita-degli-studi-di-trieste' => 'units.it',
        'universita-degli-studi-di-udine' => 'uniud.it',
        'libera-universita-di-bolzano' => 'unibz.it',
        'universita-degli-studi-di-firenze' => 'unifi.it',
        'universita-di-pisa' => 'unipi.it',
        'universita-degli-studi-di-siena' => 'unisi.it',
        'universita-per-stranieri-di-siena' => 'unistrasi.it',
        'universita-degli-studi-carlo-bo-di-urbino' => 'uniurb.it',
        'universita-degli-studi-di-macerata' => 'unimc.it',
        'universita-politecnica-delle-marche-ancona' => 'univpm.it',
        'universita-degli-studi-di-camerino' => 'unicam.it',
        'universita-degli-studi-di-perugia' => 'unipg.it',
        'universita-per-stranieri-di-perugia' => 'unistrapg.it',
        'universita-degli-studi-di-roma-la-sapienza' => 'uniroma1.it',
        'universita-degli-studi-di-roma-tor-vergata' => 'uniroma2.it',
        'universita-degli-studi-roma-tre' => 'uniroma3.it',
        'universita-degli-studi-di-roma-foro-italico' => 'uniroma4.it',
        'luiss-libera-universita-internazionale-degli-studi-sociali-guido-carli-di-roma' => 'luiss.it',
        'universita-campus-bio-medico-di-roma' => 'unicampus.it',
        'libera-universita-maria-ssassunta-lumsa-di-roma' => 'lumsa.it',
        'universita-degli-studi-della-tuscia' => 'unitus.it',
        'universita-degli-studi-di-cassino-e-del-lazio-meridionale' => 'unicas.it',
        'universita-degli-studi-di-laquila' => 'univaq.it',
        'universita-degli-studi-gabriele-dannunzio-di-chieti-e-pescara' => 'unich.it',
        'universita-degli-studi-di-teramo' => 'unite.it',
        'universita-degli-studi-del-molise' => 'unimol.it',
        'universita-degli-studi-di-bari' => 'uniba.it',
        'universita-degli-studi-di-foggia' => 'unifg.it',
        'universita-del-salento' => 'unisalento.it',
        'universita-degli-studi-della-basilicata' => 'unibas.it',
        'universita-della-calabria' => 'unical.it',
        'universita-degli-studi-di-catanzaro-magna-grecia' => 'unicz.it',
        'universita-degli-studi-mediterranea-di-reggio-calabria' => 'unirc.it',
        'universita-degli-studi-di-catania' => 'unict.it',
        'universita-degli-studi-di-messina' => 'unime.it',
        'universita-degli-studi-di-palermo' => 'unipa.it',
        'libera-universita-della-sicilia-centrale-kore-di-enna' => 'unikore.it',
        'universita-degli-studi-di-cagliari' => 'unica.it',
        'universita-degli-studi-di-sassari' => 'uniss.it',
        'humanitas-university' => 'hunimed.eu',
        'universita-degli-studi-di-ferrara' => 'unife.it',
        'universita-degli-studi-di-modena-e-reggio-emilia' => 'unimore.it',
        'universita-degli-studi-di-parma' => 'unipr.it',
        'universita-degli-studi-di-bologna' => 'unibo.it',
    ];

    private const CONTENT_SIGNALS = ['università', 'university', 'politecnico', 'ateneo', 'polytechnic'];

    public function enrich(): EnrichmentResult
    {
        $updated = 0;
        $skipped = 0;

        $universities = University::whereNull('website_url')
            ->whereIn('slug', array_keys(self::KNOWN_DOMAINS))
            ->get(['id', 'slug']);

        foreach ($universities as $university) {
            $domain = self::KNOWN_DOMAINS[$university->slug];
            $url = $this->verify($domain);

            if ($url) {
                $university->forceFill(['website_url' => $url])->save();
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new EnrichmentResult(
            updated: $updated,
            skipped: $skipped,
            summary: "Verified and set official website_url for {$updated} universities (skipped {$skipped} that failed live verification).",
        );
    }

    private function verify(string $domain): ?string
    {
        foreach (["https://www.{$domain}", "https://{$domain}"] as $candidate) {
            try {
                $response = Http::timeout(10)->withUserAgent('unihup-import/1.0')->get($candidate);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $body = Str::lower($response->body());
            foreach (self::CONTENT_SIGNALS as $signal) {
                if (str_contains($body, $signal)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
