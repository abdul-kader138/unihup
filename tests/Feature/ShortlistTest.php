<?php

namespace Tests\Feature;

use App\Filament\Pages\FindUniversities;
use App\Filament\Pages\MyApplications;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShortlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function program(): DegreeProgram
    {
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);

        return DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc Computer Science', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ]);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        return $user;
    }

    public function test_a_student_can_toggle_a_program_on_and_off_their_list(): void
    {
        $program = $this->program();
        $user = $this->student();

        Livewire::actingAs($user)
            ->test(FindUniversities::class)
            ->callTableAction('shortlist', $program);

        $this->assertDatabaseHas('shortlist_items', [
            'user_id' => $user->id,
            'degree_program_id' => $program->id,
            'status' => 'researching',
        ]);

        Livewire::actingAs($user)
            ->test(FindUniversities::class)
            ->callTableAction('shortlist', $program);

        $this->assertDatabaseMissing('shortlist_items', [
            'user_id' => $user->id,
            'degree_program_id' => $program->id,
        ]);
    }

    public function test_a_program_is_on_a_students_list_at_most_once(): void
    {
        $program = $this->program();
        $user = $this->student();

        $user->shortlistItems()->create(['degree_program_id' => $program->id, 'status' => 'researching']);

        $this->assertDatabaseCount('shortlist_items', 1);
        $this->expectException(QueryException::class);

        $user->shortlistItems()->create(['degree_program_id' => $program->id, 'status' => 'applying']);
    }

    public function test_my_applications_only_shows_the_current_users_rows(): void
    {
        $program = $this->program();
        $mine = $this->student();
        $theirs = $this->student();

        $a = ShortlistItem::create(['user_id' => $mine->id, 'degree_program_id' => $program->id, 'status' => 'applying']);
        $b = ShortlistItem::create(['user_id' => $theirs->id, 'degree_program_id' => $program->id, 'status' => 'applying']);

        Livewire::actingAs($mine)
            ->test(MyApplications::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_a_student_can_remove_a_program_from_my_applications(): void
    {
        $program = $this->program();
        $user = $this->student();
        $item = ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        Livewire::actingAs($user)
            ->test(MyApplications::class)
            ->callTableAction('delete', $item);

        $this->assertDatabaseMissing('shortlist_items', ['id' => $item->id]);
    }

    public function test_a_student_can_edit_notes_on_a_saved_program(): void
    {
        $program = $this->program();
        $user = $this->student();
        $item = ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        Livewire::actingAs($user)
            ->test(MyApplications::class)
            ->callTableAction('editNotes', $item, ['notes' => 'Deadline is in April']);

        $this->assertSame('Deadline is in April', $item->fresh()->notes);
    }
}
