<?php

namespace Tests\Feature;

use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeadlinesTest extends TestCase
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

    private function university(string $region = 'Lombardy'): University
    {
        $slug = 'u-'.Str::random(8);

        return University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan', 'region' => $region]);
    }

    private function program(University $university, string $admission = 'open'): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);

        return DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc '.Str::random(4), 'language' => 'English', 'duration_years' => 2, 'admission_type' => $admission,
        ]);
    }

    private function deadline(array $overrides = []): Deadline
    {
        return Deadline::create(array_merge([
            'scope_type' => Deadline::SCOPE_GLOBAL,
            'title' => 'A deadline',
            'category' => 'application',
            'due_at' => now()->addDays(20),
            'due_precision' => 'day',
            'is_active' => true,
        ], $overrides));
    }

    public function test_global_deadlines_are_always_relevant(): void
    {
        $user = $this->student();
        $global = $this->deadline(['title' => 'Global one']);

        $this->assertTrue(Deadline::relevantTo($user)->contains('id', $global->id));
    }

    public function test_inactive_deadlines_are_excluded(): void
    {
        $user = $this->student();
        $this->deadline(['is_active' => false, 'title' => 'Hidden']);

        $this->assertCount(0, Deadline::relevantTo($user));
    }

    public function test_university_scoped_deadline_only_shows_when_that_university_is_shortlisted(): void
    {
        $user = $this->student();
        $mine = $this->university();
        $other = $this->university();

        $program = $this->program($mine);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $relevant = $this->deadline(['scope_type' => Deadline::SCOPE_UNIVERSITY, 'scope_id' => $mine->id, 'title' => 'Mine']);
        $irrelevant = $this->deadline(['scope_type' => Deadline::SCOPE_UNIVERSITY, 'scope_id' => $other->id, 'title' => 'Other']);

        $ids = Deadline::relevantTo($user)->pluck('id');
        $this->assertContains($relevant->id, $ids);
        $this->assertNotContains($irrelevant->id, $ids);
    }

    public function test_admission_test_deadlines_only_show_with_a_restricted_program(): void
    {
        $user = $this->student();
        $test = $this->deadline(['scope_type' => Deadline::SCOPE_ADMISSION_TEST, 'title' => 'TOLC']);

        $this->assertNotContains($test->id, Deadline::relevantTo($user)->pluck('id'));

        $program = $this->program($this->university(), admission: 'restricted');
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertContains($test->id, Deadline::relevantTo($user)->pluck('id'));
    }

    public function test_scholarship_deadline_matches_on_shortlisted_university_region(): void
    {
        $user = $this->student();
        $university = $this->university('Lombardy');
        $program = $this->program($university);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        // Italian spelling of the same region — ItalianRegions should reconcile.
        $body = RegionalScholarship::create(['region' => 'Lombardia', 'body_name' => 'DSU Lombardia']);
        $deadline = $this->deadline(['scope_type' => Deadline::SCOPE_SCHOLARSHIP, 'scope_id' => $body->id, 'title' => 'DSU']);

        $this->assertContains($deadline->id, Deadline::relevantTo($user)->pluck('id'));
    }

    public function test_my_deadlines_page_loads_and_splits_past_from_upcoming(): void
    {
        $user = $this->student();
        $this->deadline(['title' => 'Upcoming', 'due_at' => now()->addDays(10)]);
        $this->deadline(['title' => 'Passed', 'due_at' => now()->subDays(5)]);

        $this->actingAs($user)->get('/my-deadlines')->assertOk()->assertSee('Upcoming')->assertSee('Passed');
    }

    public function test_ics_export_returns_a_calendar_of_relevant_deadlines_only(): void
    {
        $user = $this->student();
        $mine = $this->university();
        $program = $this->program($mine);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->deadline(['title' => 'Shown deadline']);
        $this->deadline(['scope_type' => Deadline::SCOPE_UNIVERSITY, 'scope_id' => $this->university()->id, 'title' => 'Hidden deadline']);

        $response = $this->actingAs($user)->get('/my-deadlines.ics');

        $response->assertOk();
        $this->assertStringContainsString('text/calendar', $response->headers->get('content-type'));
        $response->assertSee('BEGIN:VCALENDAR', false);
        $response->assertSee('Shown deadline', false);
        $response->assertDontSee('Hidden deadline', false);
    }

    public function test_relevant_to_accepts_a_preloaded_shortlist(): void
    {
        $user = $this->student();
        $program = $this->program($this->university());
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);
        $deadline = $this->deadline([
            'scope_type' => Deadline::SCOPE_PROGRAM,
            'scope_id' => $program->id,
            'title' => 'Program deadline',
        ]);

        $preloaded = $user->shortlistItems()->with('degreeProgram')->get();

        $this->assertContains(
            $deadline->id,
            Deadline::relevantTo($user, shortlistItems: $preloaded)->pluck('id'),
        );
    }

    public function test_scope_names_are_resolved_without_a_query_per_row(): void
    {
        $user = $this->student();
        $university = $this->university();

        foreach (range(1, 4) as $i) {
            $this->deadline([
                'scope_type' => Deadline::SCOPE_UNIVERSITY,
                'scope_id' => $university->id,
                'title' => "Uni deadline {$i}",
            ]);
        }

        $deadlines = Deadline::relevantTo($user); // warms the scope-name cache

        DB::enableQueryLog();
        $names = $deadlines->map(fn (Deadline $d) => $d->scopeName());
        DB::disableQueryLog();

        $this->assertTrue($names->every(fn (string $n) => $n === $university->display_name));
        $this->assertCount(0, DB::getQueryLog());
    }
}
