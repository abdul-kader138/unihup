<?php

namespace Tests\Feature;

use App\Filament\Pages\UniversityProfile;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UniversityProfileTest extends TestCase
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

    private function university(string $city = 'Milan'): University
    {
        $slug = 'u-'.Str::random(8);

        return University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => $city, 'region' => 'Lombardy']);
    }

    private function program(University $u, array $overrides = []): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);

        return DegreeProgram::create(array_merge([
            'university_id' => $u->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ], $overrides));
    }

    public function test_it_renders_for_a_panel_user_and_lists_the_programs(): void
    {
        $u = $this->university();
        $this->program($u, ['name' => 'MSc Robotics']);
        $this->program($u, ['name' => 'BSc Mechanics', 'degree_level' => 'bachelor']);

        Livewire::actingAs($this->student())
            ->withQueryParams(['id' => $u->id])
            ->test(UniversityProfile::class)
            ->assertOk()
            ->assertSee('MSc Robotics')
            ->assertSee('BSc Mechanics');
    }

    public function test_an_unknown_university_is_a_404(): void
    {
        Livewire::actingAs($this->student())
            ->withQueryParams(['id' => 999999])
            ->test(UniversityProfile::class)
            ->assertNotFound();
    }

    public function test_toggle_shortlist_adds_then_removes_a_program(): void
    {
        $user = $this->student();
        $u = $this->university();
        $program = $this->program($u);

        $component = Livewire::actingAs($user)
            ->withQueryParams(['id' => $u->id])
            ->test(UniversityProfile::class);

        $component->call('toggleShortlist', $program->id);
        $this->assertDatabaseHas('shortlist_items', ['user_id' => $user->id, 'degree_program_id' => $program->id]);

        $component->call('toggleShortlist', $program->id);
        $this->assertDatabaseMissing('shortlist_items', ['user_id' => $user->id, 'degree_program_id' => $program->id]);
    }

    public function test_toggle_shortlist_ignores_a_program_from_another_university(): void
    {
        $user = $this->student();
        $u = $this->university();
        $other = $this->program($this->university('Rome'));

        Livewire::actingAs($user)
            ->withQueryParams(['id' => $u->id])
            ->test(UniversityProfile::class)
            ->call('toggleShortlist', $other->id);

        $this->assertDatabaseCount('shortlist_items', 0);
    }

    public function test_it_shows_a_university_scoped_deadline(): void
    {
        $u = $this->university();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_UNIVERSITY, 'scope_id' => $u->id,
            'title' => 'Portal opens', 'category' => 'application',
            'due_at' => now()->addDays(20), 'is_active' => true,
        ]);

        Livewire::actingAs($this->student())
            ->withQueryParams(['id' => $u->id])
            ->test(UniversityProfile::class)
            ->assertSee('Portal opens');
    }
}
