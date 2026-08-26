<?php

namespace Tests\Unit;

use App\Jobs\EnrichUniversitiesJob;
use App\Jobs\ImportUniversitiesJob;
use App\Jobs\SeedRegionalScholarshipsJob;
use App\Jobs\SeedUniversityRankingsJob;
use App\Models\RegionalScholarship;
use App\Models\University;
use App\Models\UniversityRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DataSyncJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_job_runs_the_seed_importer_and_logs_the_result(): void
    {
        (new ImportUniversitiesJob('seed'))->handle();

        $this->assertGreaterThan(0, University::count());

        $activity = Activity::where('log_name', 'data-sync')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertSame('success', $activity->getProperty('status'));
        $this->assertSame('seed', $activity->getProperty('source'));
    }

    public function test_import_job_logs_failure_for_an_unknown_source(): void
    {
        (new ImportUniversitiesJob('not-a-real-source'))->handle();

        $activity = Activity::where('log_name', 'data-sync')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertSame('failed', $activity->getProperty('status'));
    }

    public function test_enrich_job_runs_each_requested_enricher_and_logs_each_result(): void
    {
        (new ImportUniversitiesJob('seed'))->handle();

        (new EnrichUniversitiesJob(['content']))->handle();

        $activities = Activity::where('log_name', 'data-sync')->where('properties->enricher', 'content')->get();
        $this->assertCount(1, $activities);
        $this->assertSame('success', $activities->first()->getProperty('status'));
    }

    public function test_seed_regional_scholarships_job_seeds_and_logs(): void
    {
        (new SeedRegionalScholarshipsJob)->handle();

        $this->assertGreaterThan(0, RegionalScholarship::count());

        $activity = Activity::where('log_name', 'data-sync')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertSame('success', $activity->getProperty('status'));
        $this->assertSame(RegionalScholarship::count(), $activity->getProperty('count'));
    }

    public function test_seed_university_rankings_job_seeds_and_logs(): void
    {
        University::create(['name' => 'Università degli studi di Padova', 'slug' => 'universita-degli-studi-di-padova', 'city' => 'Padova']);

        (new SeedUniversityRankingsJob)->handle();

        $this->assertSame(1, UniversityRanking::count());

        $activity = Activity::where('log_name', 'data-sync')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertSame('success', $activity->getProperty('status'));
        $this->assertSame(1, $activity->getProperty('count'));
    }
}
