<?php

namespace App\Services\Universities;

use App\Contracts\ImportResult;
use App\Contracts\UniversityDataImporter;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use Database\Seeders\DegreeProgramSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\UniversitySeeder;

/**
 * The default (and, in this environment, only working) data source — runs
 * the curated seeders that ship with the app. See
 * App\Services\Universities\UniversitalyImporter for the intended live
 * source, currently stubbed.
 */
class SeedDataImporter implements UniversityDataImporter
{
    public function import(): ImportResult
    {
        (new SubjectSeeder)->run();
        (new UniversitySeeder)->run();
        (new DegreeProgramSeeder)->run();

        return new ImportResult(
            universities: University::count(),
            subjects: Subject::count(),
            degreePrograms: DegreeProgram::count(),
            summary: 'Loaded the bundled curated dataset (database/seeders/*Seeder.php).',
        );
    }
}
