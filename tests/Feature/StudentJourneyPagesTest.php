<?php

namespace Tests\Feature;

use App\Filament\Pages\CompareShortlist;
use App\Filament\Pages\MyJourney;
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

class StudentJourneyPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['study_profile_completed_at' => now()], $attributes));
        $user->assignRole('panel_user');

        return $user;
    }

    private function programs(int $count): array
    {
        $subject = Subject::firstOrCreate(['slug' => 'engineering'], ['name' => 'Engineering']);
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $slug = 'university-'.Str::random(8);
            $university = University::create([
                'name' => 'University '.$slug, 'slug' => $slug, 'city' => 'Milan',
            ]);
            $out[] = DegreeProgram::create([
                'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
                'name' => "MSc Programme {$i}", 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
            ]);
        }

        return $out;
    }

    public function test_every_panel_user_can_open_the_journey_pages(): void
    {
        $user = $this->student();

        $this->actingAs($user)->get('/my-journey')->assertOk();
        $this->actingAs($user)->get('/my-applications')->assertOk();
        $this->actingAs($user)->get('/my-documents')->assertOk();
        $this->actingAs($user)->get('/compare')->assertOk();
    }

    public function test_compare_caps_at_four_programs(): void
    {
        $user = $this->student();
        $programs = $this->programs(6);

        foreach ($programs as $program) {
            ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);
        }

        $shown = Livewire::actingAs($user)->test(CompareShortlist::class)->instance()->getPrograms();

        $this->assertCount(4, $shown);
    }

    public function test_journey_summary_counts_the_students_own_data(): void
    {
        $user = $this->student();
        [$open, $restricted] = [$this->programs(1)[0], $this->programs(1)[0]];
        $restricted->update(['admission_type' => 'restricted']);

        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $open->id, 'status' => 'applying']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $restricted->id, 'status' => 'submitted']);

        $component = Livewire::actingAs($user)->test(MyJourney::class);
        $summary = $component->instance()->getSummary();

        $this->assertSame(2, $summary['shortlist_total']);
        $this->assertTrue($summary['profile_complete']);
        $this->assertArrayHasKey('Preparing application', $summary['status_counts']);

        // The restricted shortlisted program pulls the admission-test step in.
        $keys = collect($component->instance()->getChecklist())
            ->flatMap(fn ($phase) => collect($phase['steps'])->pluck('key'));
        $this->assertContains('register_admission_test', $keys);
    }
}
