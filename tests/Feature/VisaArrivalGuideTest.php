<?php

namespace Tests\Feature;

use App\Filament\Pages\VisaArrivalGuide;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaArrivalGuideTest extends TestCase
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

        $this->actingAs($user)->get('/visa-arrival')->assertOk();
    }

    public function test_it_shows_every_step_and_official_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/visa-arrival');

        $response->assertOk()
            ->assertSee('Pre-enroll on Universitaly', false)
            ->assertSee('Type D national visa', false)
            ->assertSee('permesso di soggiorno', false)
            ->assertSee('Codice Fiscale', false)
            ->assertSee('vistoperitalia.esteri.it', false);
    }

    public function test_it_links_to_the_document_recognition_guide(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/visa-arrival');

        $response->assertOk()->assertSee(route('filament.admin.pages.doc-recognition'), false);
    }

    public function test_it_shows_the_marco_polo_turandot_section_and_checklists(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/visa-arrival');

        $response->assertOk()
            ->assertSee('Marco Polo', false)
            ->assertSee('Turandot', false)
            ->assertSee('CEFR B1', false)
            // a checklist item, to prove the structured (not just prose) rendering actually renders
            ->assertSee('marca da bollo', false);
    }

    public function test_it_shows_the_post_graduation_job_seeking_permit_section(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/visa-arrival');

        $response->assertOk()
            ->assertSee('attesa occupazione', false)
            ->assertSee('Centro per l\'Impiego'); // default escaping — Blade renders the apostrophe as &#039;
    }

    public function test_it_flags_the_visa_and_permesso_steps_as_deadline_critical_in_the_ui(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/visa-arrival');

        // Two critical steps (visa financial means, permesso di soggiorno) — the badge must appear exactly twice.
        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'Deadline-critical'));
    }

    public function test_sections_group_into_four_phases_in_display_order(): void
    {
        $phases = (new VisaArrivalGuide)->getPhasedSections();

        $this->assertSame(['before', 'arrival', 'alternative', 'after'], array_keys($phases));
        $this->assertCount(3, $phases['before']['sections']);
        $this->assertCount(2, $phases['arrival']['sections']);
        $this->assertCount(1, $phases['alternative']['sections']);
        $this->assertCount(1, $phases['after']['sections']);
    }
}
