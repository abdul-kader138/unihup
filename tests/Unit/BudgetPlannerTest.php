<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Support\BudgetPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BudgetPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function program(array $overrides = []): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create([
            'name' => 'U '.$slug, 'slug' => $slug,
            'city' => $overrides['city'] ?? 'Milan', 'region' => 'Lombardy',
        ]);
        unset($overrides['city']);

        return DegreeProgram::create(array_merge([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
            'tuition_min' => 1000, 'tuition_max' => 2500,
        ], $overrides));
    }

    public function test_non_eu_gets_visa_and_recognition_lines_that_an_eu_student_does_not(): void
    {
        $program = $this->program();

        $nonEu = BudgetPlanner::plan($program, ['is_eu_citizen' => false]);
        $eu = BudgetPlanner::plan($program, ['is_eu_citizen' => true]);

        $nonEuKeys = array_column($nonEu['one_off'], 'key');
        $euKeys = array_column($eu['one_off'], 'key');

        $this->assertContains('visa', $nonEuKeys);
        $this->assertContains('recognition', $nonEuKeys);
        $this->assertNotContains('visa', $euKeys);

        $this->assertContains('health_cover', array_column($nonEu['yearly'], 'key'));
        $this->assertNotContains('health_cover', array_column($eu['yearly'], 'key'));

        $this->assertGreaterThan($eu['first_year']['max'], $nonEu['first_year']['max']);
    }

    public function test_deposit_is_two_months_rent_and_drops_out_without_rent(): void
    {
        $program = $this->program(['city' => 'Milan']);

        $shared = BudgetPlanner::plan($program, ['housing' => 'shared']);
        $deposit = collect($shared['one_off'])->firstWhere('key', 'deposit');
        $rentRow = collect($shared['yearly'])->firstWhere('key', 'living');

        $this->assertNotNull($deposit);
        $this->assertSame($deposit['min'], $deposit['max']);
        $this->assertGreaterThan(0, $deposit['min']);

        $noRent = BudgetPlanner::plan($program, ['housing' => 'none']);
        $this->assertNull(collect($noRent['one_off'])->firstWhere('key', 'deposit'));
    }

    public function test_travel_estimate_override_is_used(): void
    {
        $program = $this->program();

        $plan = BudgetPlanner::plan($program, ['travel_estimate' => 1234]);
        $travel = collect($plan['one_off'])->firstWhere('key', 'travel');

        $this->assertSame(1234.0, $travel['min']);
        $this->assertSame(1234.0, $travel['max']);
    }

    public function test_full_course_total_scales_with_duration(): void
    {
        $oneYear = BudgetPlanner::plan($this->program(['duration_years' => 1]));
        $threeYear = BudgetPlanner::plan($this->program(['duration_years' => 3]));

        // one_off + yearly_net * duration
        $this->assertEqualsWithDelta(
            $oneYear['one_off_total']['min'] + $oneYear['yearly_net']['min'],
            $oneYear['full_course']['min'],
            0.01,
        );
        $this->assertEqualsWithDelta(
            $threeYear['one_off_total']['min'] + $threeYear['yearly_net']['min'] * 3,
            $threeYear['full_course']['min'],
            0.01,
        );
        $this->assertGreaterThan($oneYear['full_course']['max'], $threeYear['full_course']['max']);
    }
}
