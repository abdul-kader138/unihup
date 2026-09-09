<?php

namespace Tests\Feature;

use App\Filament\Pages\HelpCenter;
use App\Models\CityGuide;
use App\Models\FaqEntry;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function panelUser(string $role = 'panel_user'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    // ── City guides ─────────────────────────────────────────────────────────

    public function test_city_guides_page_shows_only_published_guides(): void
    {
        CityGuide::create(['city' => 'Milan', 'intro' => 'Business capital', 'is_published' => true]);
        CityGuide::create(['city' => 'Draftville', 'intro' => 'Not ready', 'is_published' => false]);

        $this->actingAs($this->panelUser())
            ->get('/city-guide')
            ->assertOk()
            ->assertSee('Milan')
            ->assertDontSee('Draftville');
    }

    public function test_city_guide_slug_is_derived_and_for_city_matches_loosely(): void
    {
        $guide = CityGuide::create(['city' => 'Milan', 'is_published' => true]);

        $this->assertSame('milan', $guide->slug);
        $this->assertTrue(CityGuide::forCity('milan')->is($guide));
        $this->assertNull(CityGuide::forCity('Rome'));
    }

    public function test_plain_user_cannot_manage_city_guides_but_admin_can(): void
    {
        $this->actingAs($this->panelUser())->get('/city-guides')->assertForbidden();
        $this->actingAs($this->panelUser('super_admin'))->get('/city-guides')->assertOk();
    }

    // ── Help Center / FAQ ──────────────────────────────────────────────────

    public function test_help_center_lists_published_entries_and_hides_drafts(): void
    {
        FaqEntry::create(['question' => 'How do I apply?', 'answer' => 'Via each portal.', 'category' => 'Applications', 'is_published' => true]);
        FaqEntry::create(['question' => 'Secret draft question', 'answer' => '...', 'category' => 'General', 'is_published' => false]);

        $this->actingAs($this->panelUser())
            ->get('/help-center')
            ->assertOk()
            ->assertSee('How do I apply?')
            ->assertDontSee('Secret draft question');
    }

    public function test_help_center_search_filters_entries(): void
    {
        FaqEntry::create(['question' => 'Visa timing', 'answer' => 'Apply early.', 'category' => 'Visa & arrival', 'is_published' => true]);
        FaqEntry::create(['question' => 'Scholarship deadlines', 'answer' => 'Around September.', 'category' => 'Money & scholarships', 'is_published' => true]);

        Livewire::actingAs($this->panelUser())
            ->test(HelpCenter::class)
            ->set('search', 'visa')
            ->assertSee('Visa timing')
            ->assertDontSee('Scholarship deadlines');
    }

    public function test_plain_user_cannot_manage_faqs_but_admin_can(): void
    {
        $this->actingAs($this->panelUser())->get('/faqs')->assertForbidden();
        $this->actingAs($this->panelUser('super_admin'))->get('/faqs')->assertOk();
    }
}
