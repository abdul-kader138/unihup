<?php

namespace Tests\Feature;

use App\Filament\Pages\DataSync;
use App\Jobs\EnrichUniversitiesJob;
use App\Jobs\ImportUniversitiesJob;
use App\Jobs\SeedRegionalScholarshipsJob;
use App\Jobs\SeedUniversityRankingsJob;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DataSyncFullPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_running_the_full_pipeline_chains_all_four_jobs_in_order(): void
    {
        Bus::fake();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(DataSync::class)
            ->callAction('runFullPipeline');

        Bus::assertChained([
            ImportUniversitiesJob::class,
            EnrichUniversitiesJob::class,
            SeedRegionalScholarshipsJob::class,
            SeedUniversityRankingsJob::class,
        ]);
    }
}
