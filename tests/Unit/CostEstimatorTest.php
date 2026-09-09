<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\Subject;
use App\Models\University;
use App\Support\CostEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CostEstimatorTest extends TestCase
{
    use RefreshDatabase;

    private function program(array $overrides = [], string $city = 'Milan', string $region = 'Lombardy'): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => $city, 'region' => $region]);

        return DegreeProgram::create(array_merge([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ], $overrides));
    }

    public function test_it_uses_structured_program_tuition_when_present(): void
    {
        $program = $this->program(['tuition_min' => 1000, 'tuition_max' => 3000]);

        $e = CostEstimator::estimate($program, 20000, 'none');

        $this->assertSame('program', $e['tuition']['source']);
        $this->assertEqualsWithDelta(1000.0, $e['tuition']['min'], 0.01);
        $this->assertEqualsWithDelta(3000.0, $e['tuition']['max'], 0.01);
        // No rent (housing = none): living is just the flat monthly ex-rent * 12.
        $this->assertSame((float) (CostEstimator::MONTHLY_LIVING_EX_RENT * CostEstimator::MONTHS_PER_YEAR), $e['living_annual']);
    }

    public function test_it_falls_back_to_isee_band_tuition_without_structured_fees(): void
    {
        $program = $this->program();

        $low = CostEstimator::estimate($program, 15000, 'none');
        $high = CostEstimator::estimate($program, 200000, 'none');

        $this->assertSame('isee_band', $low['tuition']['source']);
        $this->assertLessThan($high['tuition']['max'], $low['tuition']['max']);
        $this->assertNotEmpty($low['notes']);
    }

    public function test_housing_choice_scales_the_rent_component(): void
    {
        $program = $this->program(city: 'Milan');

        $shared = CostEstimator::estimate($program, 20000, 'shared');
        $studio = CostEstimator::estimate($program, 20000, 'studio');

        $this->assertGreaterThan(0, $shared['rent_monthly']);
        $this->assertGreaterThan($shared['rent_monthly'], $studio['rent_monthly']);
    }

    public function test_an_eligible_scholarship_reduces_the_optimistic_net(): void
    {
        $program = $this->program(['tuition_min' => 500, 'tuition_max' => 500], region: 'Lombardy');
        RegionalScholarship::create([
            'region' => 'Lombardia', 'body_name' => 'DSU', 'amount_min' => 2000, 'amount_max' => 4000, 'isee_threshold' => 25000,
        ]);

        $withScholarship = CostEstimator::estimate($program, 20000, 'none');
        $overThreshold = CostEstimator::estimate($program, 90000, 'none');

        $this->assertEqualsWithDelta(4000.0, $withScholarship['scholarship']['max'], 0.01);
        $this->assertSame(0.0, $overThreshold['scholarship']['max']);
        $this->assertLessThan($overThreshold['net_min'], $withScholarship['net_min']);
    }

    public function test_net_never_goes_below_zero(): void
    {
        $program = $this->program(['tuition_min' => 0, 'tuition_max' => 0], region: 'Lombardy');
        RegionalScholarship::create(['region' => 'Lombardia', 'body_name' => 'Huge', 'amount_min' => 50000, 'amount_max' => 90000]);

        $e = CostEstimator::estimate($program, 10000, 'none');

        $this->assertSame(0.0, $e['net_min']);
    }

    public function test_quick_range_is_a_formatted_euro_string(): void
    {
        $program = $this->program(['tuition_min' => 1000, 'tuition_max' => 2000]);

        $this->assertMatchesRegularExpression('/^€[\d,]+–[\d,]+$/u', CostEstimator::quickRange($program));
    }
}
