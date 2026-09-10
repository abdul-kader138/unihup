<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\DataFreshnessWidget;
use App\Filament\Widgets\UserRegistrationsChart;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards against caching non-scalar values in the admin widgets: production
 * runs with config('cache.serializable_classes') === false, so a cached
 * object (Collection, Carbon, model) comes back as __PHP_Incomplete_Class
 * and the widget fatals on the second render (once the value is served from
 * the persisted store rather than the closure).
 */
class AdminWidgetCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);

        // The array store doesn't serialize; force a store that does and
        // apply the production allow-list restriction.
        config([
            'cache.default' => 'file',
            'cache.serializable_classes' => false,
        ]);
        Cache::store('file')->flush();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_widgets_survive_a_second_render_from_the_serialized_cache(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create(['created_at' => now()->subDays(2)]);

        foreach ([AdminStatsOverviewWidget::class, DataFreshnessWidget::class, UserRegistrationsChart::class] as $widget) {
            Livewire::actingAs($admin)->test($widget)->assertOk(); // populates the cache
            Livewire::actingAs($admin)->test($widget)->assertOk(); // reads it back
        }
    }

    public function test_the_registrations_chart_counts_by_day(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create(['created_at' => now()->subDays(1)->setTime(9, 0)]);

        Livewire::actingAs($admin)
            ->test(UserRegistrationsChart::class)
            ->assertOk();

        $data = Cache::get('admin.stats.registrations-14d');
        $this->assertIsArray($data);
        $this->assertSame(2, $data[now()->subDays(1)->format('Y-m-d')] ?? 0);
    }
}
