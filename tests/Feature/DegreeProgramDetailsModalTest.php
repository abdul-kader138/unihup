<?php

namespace Tests\Feature;

use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\Subject;
use App\Models\University;
use App\Models\UniversityRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DegreeProgramDetailsModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_regional_scholarships_matching_the_universitys_region(): void
    {
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Bologna', 'region' => 'Emilia-Romagna']);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Computer Science',
            'language' => 'English',
            'admission_type' => 'open',
        ]);

        RegionalScholarship::create([
            'region' => 'Emilia-Romagna',
            'body_name' => 'ER.GO',
            'description' => 'Test description.',
            'website_url' => 'https://www.er-go.it/',
        ]);

        RegionalScholarship::create([
            'region' => 'Lombardia',
            'body_name' => 'DiSCo Lombardia',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringContainsString('ER.GO', $html);
        $this->assertStringNotContainsString('DiSCo Lombardia', $html); // wrong region — must not leak in
        $this->assertStringContainsString('IELTS', $html); // LanguageProficiencyCopy::ENGLISH_NOTE
        $this->assertStringContainsString('Dichiarazione di Valore', $html); // DocumentRecognitionCopy::MODAL_NOTE
        $this->assertStringContainsString('Type D student visa', $html); // VisaArrivalCopy::MODAL_NOTE
    }

    public function test_it_shows_italian_language_guidance_for_italian_taught_programs(): void
    {
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome', 'region' => 'Lazio']);
        $subject = Subject::create(['name' => 'Law', 'slug' => 'law']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Giurisprudenza',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringContainsString('CILS', $html); // LanguageProficiencyCopy::ITALIAN_NOTE
    }

    public function test_it_renders_without_error_when_no_scholarship_matches_the_region(): void
    {
        $university = University::create(['name' => 'No Match University', 'slug' => 'no-match-university', 'city' => 'Nowhere', 'region' => null]);
        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Physics',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringNotContainsString('Regional scholarships', $html);
    }

    public function test_it_shows_the_subject_and_university_description_when_present(): void
    {
        $university = University::create([
            'name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome',
            'description' => 'A long-established public research university.',
        ]);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Computer Science',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringContainsString('Computer Science', $html);
        $this->assertStringContainsString('A long-established public research university.', $html);
    }

    public function test_it_shows_the_censis_score_breakdown_when_a_ranking_exists(): void
    {
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Computer Science',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        UniversityRanking::create([
            'university_id' => $university->id,
            'edition' => '2025/2026',
            'category' => 'grandi_non_statali',
            'position' => 1,
            'score_services' => 75,
            'score_scholarships' => 110,
            'score_facilities' => 83,
            'score_communication_digital' => 99,
            'score_internationalization' => 104,
            'score_employability' => null,
            'overall_score' => 94.2,
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringContainsString('CENSIS score breakdown', $html);
        $this->assertStringContainsString('Services', $html);
        $this->assertStringNotContainsString('Employability', $html); // null score — must not render an empty tile
    }

    public function test_it_renders_without_error_when_there_is_no_ranking(): void
    {
        $university = University::create(['name' => 'Unranked University', 'slug' => 'unranked-university', 'city' => 'Rome']);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Computer Science',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringNotContainsString('CENSIS score breakdown', $html);
    }

    public function test_it_always_shows_financial_support_info_regardless_of_region(): void
    {
        // No RegionalScholarship rows exist at all here — MAECI/ISEE Parificato are
        // national, not regional, so this must render with zero scholarship data present.
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome', 'region' => null]);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $program = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Computer Science',
            'language' => 'Italian',
            'admission_type' => 'open',
        ]);

        $html = view('filament.pages.degree-program-details', ['program' => $program])->render();

        $this->assertStringContainsString('ISEE Parificato', $html);
        $this->assertStringContainsString('MAECI', $html);
    }
}
