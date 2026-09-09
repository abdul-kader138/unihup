<?php

namespace Tests\Feature;

use App\Filament\Pages\BudgetPlanner;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetPlannerPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function studentWithProgram(): array
    {
        $user = User::factory()->create(['is_eu_citizen' => false]);
        $user->assignRole('panel_user');

        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan', 'region' => 'Lombardy']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc Data', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
            'tuition_min' => 900, 'tuition_max' => 2800,
        ]);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        return [$user, $program];
    }

    public function test_panel_user_can_open_the_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/budget-planner')->assertOk();
    }

    public function test_it_builds_a_plan_for_a_shortlisted_program(): void
    {
        [$user, $program] = $this->studentWithProgram();

        $result = Livewire::actingAs($user)
            ->test(BudgetPlanner::class)
            ->set('data.program_id', $program->id)
            ->set('data.housing', 'shared')
            ->instance()
            ->getResult();

        $this->assertNotNull($result);
        $this->assertSame($program->id, $result['program']->id);
        $this->assertGreaterThan(0, $result['plan']['first_year']['min']);
        $this->assertGreaterThan($result['plan']['first_year']['max'], $result['plan']['full_course']['max']);
        $this->assertNull($result['currency']);
    }

    public function test_choosing_a_home_currency_persists_it_and_a_rate_adds_the_currency_column(): void
    {
        [$user, $program] = $this->studentWithProgram();

        $component = Livewire::actingAs($user)
            ->test(BudgetPlanner::class)
            ->set('data.program_id', $program->id)
            ->set('data.home_currency', 'INR');

        $this->assertSame('INR', $user->fresh()->home_currency);

        $result = $component->set('data.exchange_rate', 90)->instance()->getResult();

        $this->assertNotNull($result['currency']);
        $this->assertSame('INR', $result['currency']['code']);
        $this->assertSame(90.0, $result['currency']['rate']);
    }

    public function test_an_invalid_currency_is_not_persisted(): void
    {
        [$user, $program] = $this->studentWithProgram();

        Livewire::actingAs($user)
            ->test(BudgetPlanner::class)
            ->set('data.program_id', $program->id)
            ->set('data.home_currency', 'ZZZ');

        $this->assertNull($user->fresh()->home_currency);
    }
}
