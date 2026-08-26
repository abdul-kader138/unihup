<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSyncPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_a_plain_panel_user_cannot_open_data_sync(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)->get('/data-sync')->assertForbidden();
    }

    public function test_a_panel_user_with_the_page_permission_still_cannot_open_data_sync(): void
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');
        $user->givePermissionTo('page_DataSync');

        $this->actingAs($user)->get('/data-sync')->assertForbidden();
    }

    public function test_super_admin_can_open_data_sync(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/data-sync')->assertOk();
    }
}
