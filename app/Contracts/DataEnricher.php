<?php

namespace App\Contracts;

interface DataEnricher
{
    /**
     * Fill in additional fields on existing records from a secondary
     * source. Must be additive and idempotent: only fill fields that are
     * currently empty, never overwrite a value another source (or a human
     * editor in Filament) already set.
     */
    public function enrich(): EnrichmentResult;
}
