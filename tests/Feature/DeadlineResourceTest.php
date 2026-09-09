<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_a_plain_panel_user_cannot_manage_deadlines(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/deadlines')->assertForbidden();
    }

    public function test_super_admin_can_manage_deadlines(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/deadlines')->assertOk();
    }

    public function test_every_panel_user_can_open_my_deadlines(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/my-deadlines')->assertOk();
    }
}
