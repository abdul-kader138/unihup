<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Support\WebPushSender;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
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

    private function payload(string $endpoint = 'https://push.example/xyz'): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'BPubKey', 'auth' => 'authSecret'],
        ];
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/push/subscription', $this->payload())->assertUnauthorized();
    }

    public function test_a_signed_in_user_can_store_a_subscription(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->postJson('/push/subscription', $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://push.example/xyz',
            'public_key' => 'BPubKey',
            'auth_token' => 'authSecret',
        ]);
    }

    public function test_storing_the_same_endpoint_twice_updates_in_place(): void
    {
        $user = $this->student();

        $this->actingAs($user)->postJson('/push/subscription', $this->payload())->assertOk();
        $this->actingAs($user)->postJson('/push/subscription', [
            'endpoint' => 'https://push.example/xyz',
            'keys' => ['p256dh' => 'rotated', 'auth' => 'rotated-auth'],
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['public_key' => 'rotated']);
    }

    public function test_a_user_can_remove_their_subscription(): void
    {
        $user = $this->student();
        $this->actingAs($user)->postJson('/push/subscription', $this->payload())->assertOk();

        $this->actingAs($user)
            ->deleteJson('/push/subscription', ['endpoint' => 'https://push.example/xyz'])
            ->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_deleting_only_touches_your_own_rows(): void
    {
        $mine = $this->student();
        $other = $this->student();

        PushSubscription::create([
            'user_id' => $other->id,
            'endpoint' => 'https://push.example/theirs',
            'endpoint_hash' => PushSubscription::hashFor('https://push.example/theirs'),
            'public_key' => 'p', 'auth_token' => 'a',
        ]);

        $this->actingAs($mine)
            ->deleteJson('/push/subscription', ['endpoint' => 'https://push.example/theirs'])
            ->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_sender_is_a_noop_without_vapid_keys(): void
    {
        config()->set('webpush.public_key', null);
        config()->set('webpush.private_key', null);

        $user = $this->student();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example/xyz',
            'endpoint_hash' => PushSubscription::hashFor('https://push.example/xyz'),
            'public_key' => 'p', 'auth_token' => 'a',
        ]);

        $this->assertFalse(WebPushSender::configured());
        $this->assertSame(0, WebPushSender::sendToUser($user, ['title' => 'Hi']));
    }
}
