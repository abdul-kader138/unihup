<?php

namespace Tests\Feature;

use App\Filament\Pages\MyJourney;
use App\Models\DegreeProgram;
use App\Models\JourneyProgress;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use App\Support\JourneyTemplate;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class JourneyChecklistTest extends TestCase
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

    private function program(array $overrides = []): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'engineering'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan']);

        return DegreeProgram::create(array_merge([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ], $overrides));
    }

    private function checklistKeys(User $user): array
    {
        $checklist = Livewire::actingAs($user)->test(MyJourney::class)->instance()->getChecklist();

        return collect($checklist)->flatMap(fn ($phase) => collect($phase['steps'])->pluck('key'))->all();
    }

    public function test_non_eu_student_sees_the_recognition_and_visa_steps(): void
    {
        $keys = $this->checklistKeys($this->student(['is_eu_citizen' => false]));

        $this->assertContains('recognise_qualification', $keys);
        $this->assertContains('apply_visa', $keys);
    }

    public function test_eu_student_does_not_see_the_visa_steps(): void
    {
        $keys = $this->checklistKeys($this->student(['is_eu_citizen' => true]));

        $this->assertNotContains('recognise_qualification', $keys);
        $this->assertNotContains('apply_visa', $keys);
        $this->assertContains('submit_applications', $keys); // "always" steps still there
    }

    public function test_a_restricted_shortlisted_program_adds_the_admission_test_step(): void
    {
        $user = $this->student(['is_eu_citizen' => true]);
        $program = $this->program(['admission_type' => 'restricted']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertContains('register_admission_test', $this->checklistKeys($user));
    }

    public function test_toggling_a_step_persists_and_moves_the_progress_bar(): void
    {
        $user = $this->student(['is_eu_citizen' => true]);

        $component = Livewire::actingAs($user)->test(MyJourney::class);
        $this->assertSame(0, $component->instance()->getChecklistProgress()['done']);

        $component->call('toggleStep', 'choose_subject');

        $this->assertDatabaseHas('journey_progress', [
            'user_id' => $user->id,
            'step_key' => 'choose_subject',
            'state' => JourneyProgress::STATE_DONE,
        ]);
        $this->assertSame(1, $component->instance()->getChecklistProgress()['done']);

        $component->call('toggleStep', 'choose_subject');
        $this->assertDatabaseHas('journey_progress', [
            'user_id' => $user->id,
            'step_key' => 'choose_subject',
            'state' => JourneyProgress::STATE_PENDING,
        ]);
    }

    public function test_the_page_renders_with_every_step_type_visible(): void
    {
        $user = $this->student(['is_eu_citizen' => false, 'scholarship_interest' => true]);
        $program = $this->program(['admission_type' => 'restricted', 'language' => 'Italian']);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->actingAs($user)->get('/my-journey')->assertOk();

        $keys = $this->checklistKeys($user);
        foreach (['apply_scholarships', 'italian_language_plan', 'register_admission_test', 'pre_enrol_universitaly'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_an_unknown_step_key_is_ignored(): void
    {
        $user = $this->student();

        Livewire::actingAs($user)->test(MyJourney::class)->call('toggleStep', 'not_a_real_step');

        $this->assertDatabaseCount('journey_progress', 0);
    }

    public function test_template_tokens_gate_steps_correctly(): void
    {
        $tokens = JourneyTemplate::activeTokens(
            isEuCitizen: false, wantsScholarship: true,
            hasRestrictedProgram: false, hasItalianTaughtProgram: false,
        );

        $keys = collect(JourneyTemplate::stepsForTokens($tokens))->pluck('key');

        $this->assertContains('apply_scholarships', $keys);
        $this->assertContains('pre_enrol_universitaly', $keys);
        $this->assertNotContains('register_admission_test', $keys);
        $this->assertNotContains('italian_language_plan', $keys);
    }
}
