<?php

namespace App\Contracts;

readonly class ImportResult
{
    public function __construct(
        public int $universities,
        public int $subjects,
        public int $degreePrograms,
        public string $summary,
    ) {}
}
