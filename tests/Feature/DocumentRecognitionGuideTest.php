<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRecognitionGuideTest extends TestCase
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

        $this->actingAs($user)->get('/doc-recognition')->assertOk();
    }

    public function test_it_shows_both_dov_and_cimea_sections_and_official_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $response = $this->actingAs($user)->get('/doc-recognition');

        $response->assertOk()
            ->assertSee('Dichiarazione di Valore')
            ->assertSee('Statement of Comparability')
            ->assertSee('Statement of Verification')
            ->assertSee('cimea.it', false)
            ->assertSee('mywallet.cimea-diplome.it', false);
    }
}
