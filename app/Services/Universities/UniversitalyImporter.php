<?php

namespace App\Services\Universities;

use App\Contracts\ImportResult;
use App\Contracts\UniversityDataImporter;

/**
 * Intended live data source — not implemented.
 *
 * universitaly.it (the Italian government's course/institution search
 * portal, and the closest thing to "official Universitaly data" that
 * exists) is a client-side Nuxt SPA: its institution/course search issues
 * XHR calls from JavaScript that never render into static HTML, and no
 * public REST/JSON API was discoverable (checked the page source, its
 * bundled JS, sitemap.xml and robots.txt — all 404 or SPA shell). Its
 * `lumia.cineca.it` search widget also blocks plain HTTP requests (403).
 *
 * Making this work for real requires a headless-browser-capable fetcher
 * (e.g. Playwright/Puppeteer) to drive https://www.universitaly.it/it/cerca-istituzioni
 * and https://www.universitaly.it/it/cerca-corsi, capture the XHR responses
 * those pages make internally, and map them onto University/Subject/
 * DegreeProgram. That capability isn't available in every environment this
 * app runs in, so it's kept as a swappable adapter (see
 * App\Contracts\UniversityDataImporter) rather than baked into the app —
 * swap App\Console\Commands\ImportUniversities' resolution of `--source=universitaly`
 * once a real implementation exists here.
 */
class UniversitalyImporter implements UniversityDataImporter
{
    public function import(): ImportResult
    {
        throw new \RuntimeException(
            'Live Universitaly import is not implemented — it requires a headless-browser-capable '.
            'fetcher (e.g. Playwright) to render universitaly.it\'s JavaScript search, which this '.
            'environment does not provide. Run `php artisan universities:import --source=seed` instead, '.
            'or implement this class once such tooling is available. See its class doc comment for details.'
        );
    }
}
