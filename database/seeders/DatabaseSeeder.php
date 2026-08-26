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
            SubjectSeeder::class,
            UniversitySeeder::class,
            DegreeProgramSeeder::class,
            RegionalScholarshipSeeder::class,
            UniversityRankingSeeder::class,
        ]);
    }
}
