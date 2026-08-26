<?php

namespace App\Console\Commands;

use App\Services\Universities\Enrichers\EnricherRegistry;
use Illuminate\Console\Command;

class EnrichUniversities extends Command
{
    protected $signature = 'universities:enrich {--only= : Comma-separated subset of "content,website,language" (defaults to all)}';

    protected $description = 'Fill in additional fields (admission text, official websites, language) on existing university/degree-program records';

    public function handle(): int
    {
        $only = $this->option('only');
        $keys = $only ? array_map('trim', explode(',', $only)) : array_keys(EnricherRegistry::ENRICHERS);

        foreach ($keys as $key) {
            if (! isset(EnricherRegistry::ENRICHERS[$key])) {
                $this->error("Unknown enricher \"{$key}\". Use one of: ".implode(', ', array_keys(EnricherRegistry::ENRICHERS)));

                return self::INVALID;
            }
        }

        foreach (EnricherRegistry::resolve($keys) as $key => $enricher) {
            $result = $enricher->enrich();
            $this->info("[{$key}] {$result->summary}");
        }

        return self::SUCCESS;
    }
}
