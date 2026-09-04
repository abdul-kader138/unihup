<?php

namespace Tests\Feature;

use App\Filament\Pages\SupportChat;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    /** A self-registered account gets this role — see App\Filament\Auth\Register. */
    private function actingAsPanelUser(): User
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('panel_user');
        $this->actingAs($user);

        return $user;
    }

    public function test_a_user_can_open_support_chat(): void
    {
        $this->actingAsPanelUser();

        $this->get('/support-chat')->assertOk();
    }

    public function test_opening_the_page_creates_a_portal_only_conversation(): void
    {
        $user = User::factory()->create(['whatsapp_number' => null]);
        $this->actingAs($user);

        Livewire::test(SupportChat::class);

        $conversation = WhatsAppConversation::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($conversation->wa_contact_id);
    }

    public function test_sending_a_message_records_an_inbound_row_and_notifies_staff(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $staff = User::factory()->create();
        $staff->assignRole('super_admin');

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(SupportChat::class)
            ->set('draft', 'Hi, when does enrolment close?')
            ->call('send');

        $conversation = WhatsAppConversation::where('user_id', $user->id)->firstOrFail();
        $message = $conversation->messages()->firstOrFail();

        $this->assertSame(WhatsAppMessage::DIRECTION_IN, $message->direction);
        $this->assertSame('Hi, when does enrolment close?', $message->body);
        $this->assertNotNull($conversation->fresh()->last_inbound_at);
        $this->assertSame(1, $staff->fresh()->notifications()->count());
    }

    public function test_a_message_typed_in_the_portal_never_calls_the_whatsapp_api(): void
    {
        // No HTTP fake configured — any outbound call would throw. Reaching
        // the assertion below proves send() never tried to hit Meta.
        $this->actingAs(User::factory()->create());

        Livewire::test(SupportChat::class)
            ->set('draft', 'portal only, please')
            ->call('send');

        $this->assertDatabaseHas('whatsapp_messages', ['body' => 'portal only, please']);
    }

    public function test_a_returning_user_reuses_their_existing_conversation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $first = Livewire::test(SupportChat::class)->get('conversationId');
        $second = Livewire::test(SupportChat::class)->get('conversationId');

        $this->assertSame($first, $second);
        $this->assertSame(1, WhatsAppConversation::where('user_id', $user->id)->count());
    }
}
