<?php

namespace Tests\Feature;

use App\Filament\Pages\MyApplications;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use App\Support\ApplicationSteps;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('panel_user');

        return $user;
    }

    private function shortlistItem(User $user, string $admission = 'open'): ShortlistItem
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => $admission,
        ]);

        return ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'applying']);
    }

    public function test_applicable_steps_depend_on_eu_status_and_admission_type(): void
    {
        $euItem = $this->shortlistItem($this->student(['is_eu_citizen' => true]), 'open');
        $nonEuRestricted = $this->shortlistItem($this->student(['is_eu_citizen' => false]), 'restricted');

        $euKeys = collect(ApplicationSteps::forItem($euItem, true))->pluck('key');
        $nonEuKeys = collect(ApplicationSteps::forItem($nonEuRestricted, false))->pluck('key');

        $this->assertNotContains('pre_enrolment', $euKeys);
        $this->assertNotContains('test_registered', $euKeys);
        $this->assertContains('pre_enrolment', $nonEuKeys);
        $this->assertContains('test_registered', $nonEuKeys);
        $this->assertContains('submitted', $euKeys); // "always" step present for everyone
    }

    public function test_saving_the_checklist_persists_and_percent_reflects_it(): void
    {
        $user = $this->student(['is_eu_citizen' => true]);
        $item = $this->shortlistItem($user, 'open');

        $this->assertSame(0, $item->applicationChecklist(true)['percent']);

        Livewire::actingAs($user)
            ->test(MyApplications::class)
            ->callTableAction('trackApplication', $item, ['steps' => ['portal_account', 'fee_paid']]);

        $this->assertDatabaseHas('application_progress', [
            'shortlist_item_id' => $item->id, 'step_key' => 'portal_account', 'done' => true,
        ]);
        $this->assertDatabaseHas('application_progress', [
            'shortlist_item_id' => $item->id, 'step_key' => 'submitted', 'done' => false,
        ]);

        $c = $item->fresh()->applicationChecklist(true);
        $this->assertSame(2, $c['done']);
        $this->assertGreaterThan(0, $c['percent']);
        $this->assertLessThan(100, $c['percent']);
    }

    public function test_unchecking_a_step_sets_it_back_to_not_done(): void
    {
        $user = $this->student(['is_eu_citizen' => true]);
        $item = $this->shortlistItem($user, 'open');

        Livewire::actingAs($user)->test(MyApplications::class)
            ->callTableAction('trackApplication', $item, ['steps' => ['portal_account']]);
        Livewire::actingAs($user)->test(MyApplications::class)
            ->callTableAction('trackApplication', $item, ['steps' => []]);

        $this->assertDatabaseHas('application_progress', [
            'shortlist_item_id' => $item->id, 'step_key' => 'portal_account', 'done' => false,
        ]);
        $this->assertSame(0, $item->fresh()->applicationChecklist(true)['done']);
    }

    public function test_progress_is_deleted_with_the_shortlist_item(): void
    {
        $user = $this->student(['is_eu_citizen' => true]);
        $item = $this->shortlistItem($user, 'open');
        Livewire::actingAs($user)->test(MyApplications::class)
            ->callTableAction('trackApplication', $item, ['steps' => ['portal_account']]);

        $item->delete();

        $this->assertDatabaseCount('application_progress', 0);
    }
}
