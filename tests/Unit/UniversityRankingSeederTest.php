<?php

namespace Tests\Unit;

use App\Models\University;
use App\Models\UniversityRanking;
use Database\Seeders\UniversityRankingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversityRankingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_creates_rankings_for_universities_that_exist(): void
    {
        University::create(['name' => 'Università degli studi di Padova', 'slug' => 'universita-degli-studi-di-padova', 'city' => 'Padova']);
        // Deliberately not creating "universita-degli-studi-di-bologna" — its ranking row must be skipped, not error.

        (new UniversityRankingSeeder)->run();

        $this->assertSame(1, UniversityRanking::count());
        $this->assertSame('mega_statali', UniversityRanking::first()->category);
    }

    public function test_it_is_idempotent(): void
    {
        University::create(['name' => 'Università degli studi di Padova', 'slug' => 'universita-degli-studi-di-padova', 'city' => 'Padova']);

        (new UniversityRankingSeeder)->run();
        (new UniversityRankingSeeder)->run();

        $this->assertSame(1, UniversityRanking::count());
    }

    public function test_non_state_universities_have_no_employability_score(): void
    {
        University::create(['name' => 'Bocconi', 'slug' => 'universita-commerciale-luigi-bocconi-di-milano', 'city' => 'Milano']);

        (new UniversityRankingSeeder)->run();

        $ranking = UniversityRanking::first();
        $this->assertSame('grandi_non_statali', $ranking->category);
        $this->assertNull($ranking->score_employability);
        $this->assertNotNull($ranking->score_services);
    }
}
