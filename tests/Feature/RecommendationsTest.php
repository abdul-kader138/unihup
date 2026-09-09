<?php

namespace Tests\Feature;

use App\Models\CityGuide;
use App\Models\DegreeProgram;
use App\Models\ScholarshipTracker;
use App\Models\ShortlistItem;
use App\Models\StudentDocument;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use App\Support\Recommendations;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'study_profile_completed_at' => now(),
            'is_eu_citizen' => true,
            'english_level' => 'c1',
        ], $attributes));
        $user->assignRole('panel_user');

        return $user;
    }

    private function program(array $overrides = []): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'engineering'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create([
            'name' => 'U '.$slug, 'slug' => $slug, 'city' => $overrides['city'] ?? 'Milan',
        ]);
        unset($overrides['city']);

        return DegreeProgram::create(array_merge([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ], $overrides));
    }

    private function keys(User $user): array
    {
        return array_column(Recommendations::for($user), 'key');
    }

    public function test_a_student_with_no_shortlist_is_pointed_at_find_universities(): void
    {
        $recs = Recommendations::for($this->student());

        $this->assertCount(1, $recs);
        $this->assertSame('start-shortlist', $recs[0]['key']);
    }

    public function test_an_empty_document_vault_is_surfaced_once_there_is_a_shortlist(): void
    {
        $user = $this->student();
        $program = $this->program();
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertContains('setup-vault', $this->keys($user));

        StudentDocument::create(['user_id' => $user->id, 'type' => 'passport', 'status' => 'ready']);

        $this->assertNotContains('setup-vault', $this->keys($user));
    }

    public function test_more_programs_in_the_same_subject_are_suggested(): void
    {
        $user = $this->student();
        $saved = $this->program(['name' => 'MSc Robotics']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $saved->id, 'status' => 'researching']);

        $other = $this->program(['name' => 'MSc Mechatronics']);

        $this->assertContains('program-'.$other->id, $this->keys($user));
    }

    public function test_italian_taught_suggestions_need_a_b1_italian_level(): void
    {
        $user = $this->student(['italian_level' => 'a1']);
        $saved = $this->program(['name' => 'MSc Robotics']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $saved->id, 'status' => 'researching']);

        $italian = $this->program(['name' => 'LM Ingegneria', 'language' => 'Italian']);

        $this->assertNotContains('program-'.$italian->id, $this->keys($user));

        $user->update(['italian_level' => 'b2']);

        $this->assertContains('program-'.$italian->id, $this->keys($user));
    }

    public function test_a_city_guide_is_recommended_for_a_shortlisted_city(): void
    {
        $user = $this->student();
        $program = $this->program(['city' => 'Bologna']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        CityGuide::create(['city' => 'Bologna', 'is_published' => true, 'housing' => 'Rooms from €350.']);

        $this->assertContains('city-bologna', $this->keys($user));
    }

    public function test_scholarship_nudge_only_when_interested_and_not_tracking_yet(): void
    {
        $user = $this->student(['scholarship_interest' => true]);
        $program = $this->program();
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertContains('match-scholarships', $this->keys($user));

        ScholarshipTracker::create([
            'user_id' => $user->id, 'kind' => ScholarshipTracker::KIND_NATIONAL,
            'ref' => 'maeci', 'label' => 'MAECI', 'status' => 'interested',
        ]);

        $this->assertNotContains('match-scholarships', $this->keys($user));
    }

    public function test_below_minimum_language_level_raises_a_recheck_card(): void
    {
        $user = $this->student(['english_level' => 'a1']);
        $program = $this->program(['language' => 'English']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertContains('eligibility-'.$program->id, $this->keys($user));
    }

    public function test_the_list_is_capped(): void
    {
        $user = $this->student(['scholarship_interest' => true, 'is_eu_citizen' => false]);

        foreach (['Milan', 'Bologna', 'Turin'] as $i => $city) {
            $program = $this->program(['name' => "MSc {$city} {$i}", 'city' => $city]);
            ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);
            CityGuide::create(['city' => $city, 'is_published' => true, 'housing' => 'Rooms.']);
        }

        $this->assertLessThanOrEqual(Recommendations::LIMIT, count(Recommendations::for($user)));
    }
}
