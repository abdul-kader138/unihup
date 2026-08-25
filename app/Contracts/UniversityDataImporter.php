<?php

namespace App\Contracts;

interface UniversityDataImporter
{
    /**
     * Import (or refresh) university/subject/degree-program data. Idempotent —
     * safe to run repeatedly (matches records by slug/natural key rather than
     * inserting duplicates).
     */
    public function import(): ImportResult;
}
