<?php

namespace Tests\Feature;

use App\Filament\Pages\FindUniversities;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FindUniversitiesSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        // Exercise the Scout path with the no-service collection engine.
        config(['scout.driver' => 'collection']);
    }

    private function program(string $programName, string $uniName, string $city, string $language = 'English'): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'cs'], ['name' => 'Computer Science']);
        $university = University::create(['name' => $uniName, 'slug' => str($uniName)->slug(), 'city' => $city]);

        return DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => $programName, 'language' => $language, 'duration_years' => 3, 'admission_type' => 'open',
        ]);
    }

    public function test_search_matches_across_program_university_and_city_via_scout(): void
    {
        $match = $this->program('Data Science', 'Politecnico di Milano', 'Milan');
        $noMatch = $this->program('Ancient History', 'Universita di Bologna', 'Bologna');

        $user = User::factory()->create();
        $user->assignRole('panel_user');

        Livewire::actingAs($user)
            ->test(FindUniversities::class)
            ->set('tableSearch', 'milan')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$noMatch]);
    }

    public function test_empty_search_shows_everything(): void
    {
        $a = $this->program('Data Science', 'Politecnico di Milano', 'Milan');
        $b = $this->program('Ancient History', 'Universita di Bologna', 'Bologna');

        $user = User::factory()->create();
        $user->assignRole('panel_user');

        Livewire::actingAs($user)
            ->test(FindUniversities::class)
            ->assertCanSeeTableRecords([$a, $b]);
    }
}
