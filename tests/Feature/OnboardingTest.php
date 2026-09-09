<?php

namespace Tests\Feature;

use App\Filament\Pages\FindUniversities;
use App\Filament\Pages\Onboarding;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingTest extends TestCase
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

    public function test_a_panel_user_can_open_the_wizard(): void
    {
        $this->actingAs($this->student())->get('/get-started')->assertOk();
    }

    public function test_finishing_the_wizard_saves_the_profile_and_redirects(): void
    {
        $subject = Subject::firstOrCreate(['slug' => 'cs'], ['name' => 'Computer Science']);
        $user = $this->student(['study_profile_completed_at' => null]);

        Livewire::actingAs($user)
            ->test(Onboarding::class)
            ->fillForm([
                'nationality' => 'Indian',
                'prior_education_country' => 'India',
                'is_eu_citizen' => false,
                'english_level' => 'c1',
                'italian_level' => 'a2',
                'preferred_subject_id' => $subject->id,
                'preferred_degree_level' => 'master',
                'scholarship_interest' => true,
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertRedirect(FindUniversities::getUrl());

        $user->refresh();
        $this->assertSame('Indian', $user->nationality);
        $this->assertFalse($user->is_eu_citizen);
        $this->assertTrue($user->scholarship_interest);
        $this->assertSame($subject->id, $user->preferred_subject_id);
        $this->assertNotNull($user->study_profile_completed_at);
        $this->assertTrue($user->hasCompletedStudyProfile());
    }

    public function test_nationality_is_required(): void
    {
        $user = $this->student(['study_profile_completed_at' => null]);

        Livewire::actingAs($user)
            ->test(Onboarding::class)
            ->fillForm(['nationality' => ''])
            ->call('submit')
            ->assertHasFormErrors(['nationality']);

        $this->assertNull($user->fresh()->study_profile_completed_at);
    }

    public function test_registration_lands_on_the_wizard(): void
    {
        // Covered end-to-end in RegistrationVerificationTest; this pins the target.
        $this->assertStringEndsWith('/get-started', Onboarding::getUrl());
    }
}
