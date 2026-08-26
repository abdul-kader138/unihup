<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Services\Universities\Enrichers\AdmissionContentEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionContentEnricherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_missing_text_without_overwriting_existing_notes(): void
    {
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);
        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);

        $blank = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'bachelor',
            'name' => 'Physics',
            'admission_type' => 'open',
        ]);

        $curated = DegreeProgram::create([
            'university_id' => $university->id,
            'subject_id' => $subject->id,
            'degree_level' => 'master',
            'name' => 'Physics (Master)',
            'admission_type' => 'restricted',
            'admission_notes' => 'A hand-written note that must survive.',
            'tuition_note' => 'Custom tuition note.',
            'application_window_note' => 'Custom window note.',
        ]);

        $result = (new AdmissionContentEnricher)->enrich();

        $this->assertSame(1, $result->updated); // only the blank one needed filling

        $blank->refresh();
        $this->assertNotNull($blank->admission_notes);
        $this->assertNotNull($blank->tuition_note);
        $this->assertNotNull($blank->application_window_note);

        $curated->refresh();
        $this->assertSame('A hand-written note that must survive.', $curated->admission_notes);
        $this->assertSame('Custom tuition note.', $curated->tuition_note);
        $this->assertSame('Custom window note.', $curated->application_window_note);
    }
}
