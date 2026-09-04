<?php

namespace Tests\Feature;

use App\Filament\Auth\Register;
use App\Filament\Resources\UniversityResource\Pages\ListUniversities;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindUniversitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_a_new_registration_lands_on_find_universities(): void
    {
        \Livewire\Livewire::test(Register::class)
            ->fillForm([
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'password' => 'Password123',
                'passwordConfirmation' => 'Password123',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect('/find-universities');

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('panel_user'));
    }

    public function test_registration_requires_acknowledgement_when_a_whatsapp_number_is_entered(): void
    {
        \Livewire\Livewire::test(Register::class)
            ->fillForm([
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'whatsapp@example.com',
                'whatsapp_country_code' => '+39',
                'whatsapp_local_number' => '333 123 4567',
                'whatsapp_opt_in' => false,
                'password' => 'Password123',
                'passwordConfirmation' => 'Password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['whatsapp_opt_in']);
    }

    public function test_registration_rejects_an_invalid_whatsapp_number(): void
    {
        \Livewire\Livewire::test(Register::class)
            ->fillForm([
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'invalid-whatsapp@example.com',
                'whatsapp_country_code' => '+39',
                'whatsapp_local_number' => '123',
                'whatsapp_opt_in' => true,
                'password' => 'Password123',
                'passwordConfirmation' => 'Password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['whatsapp_local_number']);
    }

    public function test_every_registered_user_can_open_the_find_universities_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/find-universities')->assertOk();
    }

    public function test_a_plain_panel_user_cannot_manage_universities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/universities')->assertForbidden();
    }

    public function test_super_admin_can_manage_universities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/universities')->assertOk();
    }

    public function test_the_page_filters_programs_by_subject_and_degree_level(): void
    {
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);
        $otherSubject = Subject::create(['name' => 'Law', 'slug' => 'law']);
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);

        $match = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'Computer Science', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ]);
        $nonMatch = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $otherSubject->id, 'degree_level' => 'bachelor',
            'name' => 'Law', 'language' => 'Italian', 'duration_years' => 5, 'admission_type' => 'open',
        ]);

        $user = User::factory()->create(['preferred_subject_id' => $subject->id, 'preferred_degree_level' => 'bachelor']);
        $user->assignRole('panel_user');

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Filament\Pages\FindUniversities::class)
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$nonMatch]);
    }

    public function test_admin_can_create_a_degree_program(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $subject = Subject::create(['name' => 'Physics', 'slug' => 'physics']);
        $university = University::create(['name' => 'Test University', 'slug' => 'test-university', 'city' => 'Rome']);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\DegreeProgramResource\Pages\CreateDegreeProgram::class)
            ->fillForm([
                'university_id' => $university->id,
                'subject_id' => $subject->id,
                'name' => 'Physics',
                'degree_level' => 'bachelor',
                'language' => 'Italian',
                'duration_years' => 3,
                'admission_type' => 'open',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('degree_programs', ['name' => 'Physics', 'university_id' => $university->id]);
    }

    public function test_admin_university_list_page_loads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        \Livewire\Livewire::test(ListUniversities::class)->assertOk();
    }
}
