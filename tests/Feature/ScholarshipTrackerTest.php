<?php

namespace Tests\Feature;

use App\Filament\Pages\MyScholarships;
use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\ScholarshipTracker;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ScholarshipTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        return $user;
    }

    private function shortlist(User $user, string $region, string $level = 'master'): void
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'X', 'region' => $region]);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => $level,
            'name' => 'P', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ]);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);
    }

    public function test_regional_bodies_match_on_shortlisted_university_region(): void
    {
        $user = $this->student();
        $this->shortlist($user, 'Lombardy');
        $match = RegionalScholarship::create(['region' => 'Lombardia', 'body_name' => 'DSU Lombardia']);
        $other = RegionalScholarship::create(['region' => 'Sicilia', 'body_name' => 'ERSU Palermo']);

        $matched = Livewire::actingAs($user)->test(MyScholarships::class)->instance()->getMatched();
        $refs = $matched['regional']->pluck('ref');

        $this->assertContains((string) $match->id, $refs);
        $this->assertNotContains((string) $other->id, $refs);
    }

    public function test_invest_your_talent_only_shows_with_a_master_program(): void
    {
        $bachelorUser = $this->student();
        $this->shortlist($bachelorUser, 'Lazio', 'bachelor');
        $masterUser = $this->student();
        $this->shortlist($masterUser, 'Lazio', 'master');

        $bKeys = Livewire::actingAs($bachelorUser)->test(MyScholarships::class)->instance()->getMatched()['national']->pluck('ref');
        $mKeys = Livewire::actingAs($masterUser)->test(MyScholarships::class)->instance()->getMatched()['national']->pluck('ref');

        $this->assertNotContains('iyt', $bKeys);
        $this->assertContains('iyt', $mKeys);
        $this->assertContains('maeci', $bKeys); // "always" national option
    }

    public function test_track_adds_a_row_once(): void
    {
        $user = $this->student();

        Livewire::actingAs($user)->test(MyScholarships::class)
            ->call('track', 'national', 'maeci', 'MAECI government scholarships')
            ->call('track', 'national', 'maeci', 'MAECI government scholarships');

        $this->assertDatabaseCount('scholarship_tracker', 1);
        $this->assertDatabaseHas('scholarship_tracker', [
            'user_id' => $user->id, 'kind' => 'national', 'ref' => 'maeci', 'status' => 'interested',
        ]);
    }

    public function test_page_loads_and_tracker_only_shows_own_rows(): void
    {
        $mine = $this->student();
        $theirs = $this->student();
        $a = ScholarshipTracker::create(['user_id' => $mine->id, 'kind' => 'national', 'ref' => 'maeci', 'label' => 'MAECI']);
        $b = ScholarshipTracker::create(['user_id' => $theirs->id, 'kind' => 'national', 'ref' => 'maeci', 'label' => 'MAECI']);

        $this->actingAs($mine)->get('/my-scholarships')->assertOk();

        Livewire::actingAs($mine)->test(MyScholarships::class)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_tracker_rows_are_removed_with_the_user(): void
    {
        $user = $this->student();
        ScholarshipTracker::create(['user_id' => $user->id, 'kind' => 'national', 'ref' => 'maeci', 'label' => 'MAECI']);

        $user->delete();

        $this->assertDatabaseCount('scholarship_tracker', 0);
    }
}
