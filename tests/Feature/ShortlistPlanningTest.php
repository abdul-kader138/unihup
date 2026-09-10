<?php

namespace Tests\Feature;

use App\Filament\Pages\FindUniversities;
use App\Filament\Pages\MyApplications;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShortlistPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('panel_user');

        return $user;
    }

    private function program(string $admission = 'open', string $language = 'English'): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'cs'], ['name' => 'Computer Science']);
        $university = University::create(['name' => 'Uni '.uniqid(), 'slug' => 'uni-'.uniqid(), 'city' => 'Rome']);

        return DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc', 'language' => $language, 'duration_years' => 3, 'admission_type' => $admission,
        ]);
    }

    public function test_saving_a_program_seeds_a_reach_target_or_safety_tier(): void
    {
        $program = $this->program(admission: 'restricted');
        $user = $this->student([
            'study_profile_completed_at' => now(),
            'english_level' => 'b2',
            'is_eu_citizen' => true,
        ]);

        Livewire::actingAs($user)
            ->test(FindUniversities::class)
            ->callTableAction('shortlist', $program);

        $item = ShortlistItem::firstWhere('degree_program_id', $program->id);

        $this->assertContains($item->tier, array_keys(ShortlistItem::TIERS));
        // Restricted access with only a "check" eligibility reads as a reach.
        $this->assertSame('reach', $item->tier);
    }

    public function test_a_clean_eligibility_reads_as_a_safety(): void
    {
        $program = $this->program(admission: 'open');
        $user = $this->student([
            'study_profile_completed_at' => now(),
            'english_level' => 'c1',
            'is_eu_citizen' => true,
        ]);

        $item = new ShortlistItem(['user_id' => $user->id, 'degree_program_id' => $program->id]);
        $item->setRelation('degreeProgram', $program);
        $item->setRelation('user', $user);

        $this->assertSame('safety', $item->suggestedTier());
    }

    public function test_my_applications_is_reorderable_and_exposes_the_tier_column(): void
    {
        $user = $this->student(['study_profile_completed_at' => now()]);
        $a = $this->program();
        $b = $this->program();
        $ia = ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $a->id, 'status' => 'researching', 'sort_order' => 1]);
        $ib = ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $b->id, 'status' => 'researching', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(MyApplications::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$ib, $ia], inOrder: true) // sort_order asc
            ->assertTableColumnExists('tier')
            ->assertTableColumnExists('data_freshness');
    }

    public function test_a_program_is_stale_when_never_verified_or_old(): void
    {
        $fresh = $this->program();
        $fresh->update(['last_verified_at' => now()->subDays(10)]);
        $stale = $this->program();
        $stale->update(['last_verified_at' => now()->subDays(120)]);
        $never = $this->program();

        $this->assertFalse($fresh->fresh()->isStale());
        $this->assertTrue($stale->fresh()->isStale());
        $this->assertTrue($never->fresh()->isStale());
        $this->assertSame('Not yet verified', $never->fresh()->verificationLabel());
    }
}
