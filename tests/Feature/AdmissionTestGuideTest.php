<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionTestGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_every_registered_user_can_open_the_guide(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/admission-tests')->assertOk();
    }

    public function test_it_shows_tolc_imat_and_semestre_filtro_sections_and_official_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/admission-tests');

        $response->assertOk()
            ->assertSee('TOLC (Test OnLine CISIA)')
            ->assertSee('IMAT')
            ->assertSee('semestre filtro')
            ->assertSee('cisiaonline.it', false)
            ->assertSee('universitaly.it', false);
    }

    public function test_it_flags_the_imat_section_as_deadline_critical(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/admission-tests');

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Deadline-critical'));
    }

    public function test_it_links_to_the_visa_arrival_guide(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/admission-tests');

        $response->assertOk()->assertSee(route('filament.admin.pages.visa-arrival'), false);
    }
}
