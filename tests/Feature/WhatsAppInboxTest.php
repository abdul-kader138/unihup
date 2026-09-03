<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppInbox;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    private function conversation(array $overrides = []): WhatsAppConversation
    {
        return WhatsAppConversation::create(array_merge([
            'wa_contact_id' => '393331234567',
            'wa_contact_name' => 'Mario Rossi',
            'status' => WhatsAppConversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(5),
        ], $overrides));
    }

    public function test_a_plain_user_cannot_open_the_inbox(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/whatsapp-inbox')->assertForbidden();
    }

    public function test_super_admin_can_open_the_inbox(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/whatsapp-inbox')->assertOk();
    }

    public function test_reply_within_the_window_queues_a_send_and_records_the_message(): void
    {
        Queue::fake();
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation();

        Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id)
            ->set('reply', 'Buongiorno, come possiamo aiutarla?')
            ->call('sendReply');

        $message = $conversation->messages()->firstOrFail();
        $this->assertSame(WhatsAppMessage::DIRECTION_OUT, $message->direction);
        $this->assertSame('Buongiorno, come possiamo aiutarla?', $message->body);
        $this->assertSame(WhatsAppMessage::STATUS_QUEUED, $message->status);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
        $this->assertSame(WhatsAppConversation::STATUS_PENDING, $conversation->fresh()->status);
    }

    public function test_reply_outside_the_window_is_blocked(): void
    {
        Queue::fake();
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation(['last_inbound_at' => now()->subDays(2)]);

        Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id)
            ->set('reply', 'too late')
            ->call('sendReply');

        $this->assertSame(0, $conversation->messages()->count());
        Queue::assertNothingPushed();
    }

    public function test_reopen_template_queues_a_template_send(): void
    {
        Queue::fake();
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation(['last_inbound_at' => now()->subDays(3)]);

        Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id)
            ->call('sendReopenTemplate');

        $message = $conversation->messages()->firstOrFail();
        $this->assertSame('template', $message->type);

        Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
            return $job->templateName === config('services.whatsapp.reopen_template');
        });
    }

    public function test_assign_to_me_and_close_update_the_conversation(): void
    {
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation();

        $component = Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id)
            ->call('assignToMe')
            ->call('setStatus', WhatsAppConversation::STATUS_CLOSED);

        $this->assertSame(auth()->id(), $conversation->fresh()->assigned_to);
        $this->assertSame(WhatsAppConversation::STATUS_CLOSED, $conversation->fresh()->status);
    }
}
