<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            // Catalogue seeders — all updateOrCreate on stable natural keys,
            // so they're idempotent and safe to re-run on every deploy
            // (deploy.sh calls `db:seed --force`). Order matters: subjects and
            // universities before the programs that reference them.
            SubjectSeeder::class,
            UniversitySeeder::class,
            DegreeProgramSeeder::class,
            RegionalScholarshipSeeder::class,
            UniversityRankingSeeder::class,
            DeadlineSeeder::class,
            CityGuideSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
