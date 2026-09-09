<?php

namespace Tests\Feature;

use App\Filament\Pages\AdmissionTestGuide;
use App\Filament\Pages\MyJourney;
use App\Filament\Pages\VisaArrivalGuide;
use App\Models\User;
use App\Support\AdmissionTestCopy;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveGuidesTest extends TestCase
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

    public function test_marking_a_guide_section_read_persists_and_updates_progress(): void
    {
        $user = $this->student();
        $key = AdmissionTestCopy::SECTIONS[0]['key'];

        $component = Livewire::actingAs($user)->test(AdmissionTestGuide::class);
        $this->assertSame(0, $component->instance()->guideProgress()['done']);

        $component->call('toggleGuideSection', $key);

        $this->assertDatabaseHas('journey_progress', [
            'user_id' => $user->id,
            'step_key' => "guide:admission-tests:{$key}",
            'state' => 'done',
        ]);
        $this->assertSame(1, $component->instance()->guideProgress()['done']);

        $component->call('toggleGuideSection', $key);
        $this->assertDatabaseHas('journey_progress', [
            'step_key' => "guide:admission-tests:{$key}",
            'state' => 'pending',
        ]);
    }

    public function test_an_unknown_section_key_is_ignored(): void
    {
        $user = $this->student();

        Livewire::actingAs($user)->test(AdmissionTestGuide::class)->call('toggleGuideSection', 'nope');

        $this->assertDatabaseCount('journey_progress', 0);
    }

    public function test_visa_guide_sections_key_on_step_number(): void
    {
        $user = $this->student();

        Livewire::actingAs($user)->test(VisaArrivalGuide::class)->call('toggleGuideSection', 'step-1');

        $this->assertDatabaseHas('journey_progress', [
            'user_id' => $user->id,
            'step_key' => 'guide:visa-arrival:step-1',
            'state' => 'done',
        ]);
    }

    public function test_my_journey_counts_guide_sections_read(): void
    {
        $user = $this->student();
        $key = AdmissionTestCopy::SECTIONS[0]['key'];

        Livewire::actingAs($user)->test(AdmissionTestGuide::class)->call('toggleGuideSection', $key);

        $progress = Livewire::actingAs($user)->test(MyJourney::class)->instance()->getGuidesProgress();
        $this->assertSame(1, $progress['done']);
        $this->assertGreaterThan(1, $progress['total']);
    }

    public function test_all_three_guides_still_render(): void
    {
        $user = $this->student();

        $this->actingAs($user)->get('/admission-tests')->assertOk();
        $this->actingAs($user)->get('/doc-recognition')->assertOk();
        $this->actingAs($user)->get('/visa-arrival')->assertOk();
    }
}
