<?php

namespace Tests\Feature;

use App\Models\ScholarshipTracker;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineScholarshipSectionTest extends TestCase
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

    public function test_tracked_scholarship_deadlines_appear_on_the_deadlines_page(): void
    {
        $user = $this->student();
        ScholarshipTracker::create([
            'user_id' => $user->id, 'kind' => 'national', 'ref' => 'maeci',
            'label' => 'MAECI government scholarships', 'status' => 'preparing',
            'deadline_at' => now()->addDays(20),
        ]);
        ScholarshipTracker::create([
            'user_id' => $user->id, 'kind' => 'national', 'ref' => 'iyt',
            'label' => 'No deadline set', 'status' => 'interested', 'deadline_at' => null,
        ]);

        $this->actingAs($user)->get('/my-deadlines')
            ->assertOk()
            ->assertSee('MAECI government scholarships')
            ->assertSee('Scholarship deadlines')
            ->assertDontSee('No deadline set');
    }

    public function test_ics_export_includes_tracked_scholarship_deadlines(): void
    {
        $user = $this->student();
        ScholarshipTracker::create([
            'user_id' => $user->id, 'kind' => 'regional', 'ref' => '99',
            'label' => 'DSU Somewhere', 'status' => 'interested', 'deadline_at' => now()->addDays(15),
        ]);

        $this->actingAs($user)->get('/my-deadlines.ics')
            ->assertOk()
            ->assertSee('DSU Somewhere — scholarship deadline', false);
    }
}
