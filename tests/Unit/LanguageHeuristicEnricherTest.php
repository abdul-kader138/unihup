<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Services\Universities\Enrichers\LanguageHeuristicEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageHeuristicEnricherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flags_clear_english_titles_and_leaves_ambiguous_ones_alone(): void
    {
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);

        $italian = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id,
            'degree_level' => 'bachelor', 'name' => 'Ingegneria Informatica e dell\'Automazione',
            'language' => 'Italian', 'admission_type' => 'open',
        ]);

        $english = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id,
            'degree_level' => 'master', 'name' => 'Data Science and Management Engineering',
            'language' => 'Italian', 'admission_type' => 'open',
        ]);

        $ambiguous = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id,
            'degree_level' => 'bachelor', 'name' => 'Robotics',
            'language' => 'Italian', 'admission_type' => 'open',
        ]);

        $result = (new LanguageHeuristicEnricher)->enrich();

        $this->assertSame(1, $result->updated);
        $this->assertSame('Italian', $italian->fresh()->language);
        $this->assertSame('English', $english->fresh()->language);
        $this->assertSame('Italian', $ambiguous->fresh()->language); // no strong signal either way — left alone
    }
}
