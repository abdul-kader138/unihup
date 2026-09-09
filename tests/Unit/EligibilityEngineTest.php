<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use App\Support\EligibilityEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EligibilityEngineTest extends TestCase
{
    use RefreshDatabase;

    private function program(array $overrides = []): DegreeProgram
    {
        $subject = Subject::create(['name' => 'CS', 'slug' => 'cs-'.uniqid()]);
        $university = University::create(['name' => 'U', 'slug' => 'u-'.uniqid(), 'city' => 'Rome']);

        return DegreeProgram::create(array_merge([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ], $overrides));
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'study_profile_completed_at' => now(),
            'is_eu_citizen' => true,
            'english_level' => 'b2',
            'italian_level' => 'none',
        ], $attributes));
    }

    public function test_no_study_profile_yields_a_check_verdict(): void
    {
        $result = EligibilityEngine::assess($this->program(), User::factory()->create(['study_profile_completed_at' => null]));

        $this->assertSame(EligibilityEngine::CHECK, $result['verdict']);
    }

    public function test_english_program_with_b2_english_is_eligible(): void
    {
        $result = EligibilityEngine::assess($this->program(), $this->user(['english_level' => 'b2']));

        $this->assertSame(EligibilityEngine::ELIGIBLE, $result['verdict']);
    }

    public function test_english_program_with_a1_english_is_ineligible(): void
    {
        $result = EligibilityEngine::assess($this->program(), $this->user(['english_level' => 'a1']));

        $this->assertSame(EligibilityEngine::INELIGIBLE, $result['verdict']);
    }

    public function test_italian_program_with_no_italian_is_ineligible(): void
    {
        $result = EligibilityEngine::assess(
            $this->program(['language' => 'Italian']),
            $this->user(['italian_level' => 'none']),
        );

        $this->assertSame(EligibilityEngine::INELIGIBLE, $result['verdict']);
    }

    public function test_restricted_program_downgrades_to_check_with_a_test_reason(): void
    {
        $result = EligibilityEngine::assess(
            $this->program(['admission_type' => 'restricted']),
            $this->user(['english_level' => 'c1']),
        );

        $this->assertSame(EligibilityEngine::CHECK, $result['verdict']);
        $this->assertStringContainsString('admission test', implode(' ', $result['reasons']));
    }

    public function test_non_eu_student_gets_a_visa_reason(): void
    {
        $result = EligibilityEngine::assess($this->program(), $this->user(['is_eu_citizen' => false]));

        $this->assertStringContainsString('Type D visa', implode(' ', $result['reasons']));
    }
}
