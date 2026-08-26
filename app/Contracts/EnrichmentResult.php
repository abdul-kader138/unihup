<?php

namespace App\Contracts;

readonly class EnrichmentResult
{
    public function __construct(
        public int $updated,
        public int $skipped,
        public string $summary,
    ) {}
}
