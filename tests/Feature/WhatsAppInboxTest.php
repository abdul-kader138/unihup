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

    public function test_the_conversation_list_paginates_instead_of_loading_everything(): void
    {
        $this->actingAsSuperAdmin();

        // One more than a single page (20) — the point is only 20 load per
        // request, not that they all render in the initial query.
        for ($i = 0; $i < 21; $i++) {
            $this->conversation(['wa_contact_id' => "39333000{$i}"]);
        }

        $page1 = Livewire::test(WhatsAppInbox::class);
        $this->assertCount(20, $page1->instance()->conversations->items());
        $this->assertTrue($page1->instance()->conversations->hasMorePages());
    }

    public function test_only_the_most_recent_messages_load_until_asked_for_more(): void
    {
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation();

        for ($i = 0; $i < 45; $i++) {
            $conversation->messages()->create([
                'direction' => WhatsAppMessage::DIRECTION_IN,
                'type' => 'text',
                'body' => "message {$i}",
                'status' => WhatsAppMessage::STATUS_DELIVERED,
            ]);
        }

        $component = Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id);

        $this->assertCount(40, $component->instance()->activeMessages);
        $this->assertTrue($component->instance()->hasMoreMessages);

        $component->call('loadEarlierMessages');

        $this->assertCount(45, $component->instance()->activeMessages);
        $this->assertFalse($component->instance()->hasMoreMessages);
    }

    public function test_replying_to_a_portal_only_conversation_never_queues_a_whatsapp_send(): void
    {
        Queue::fake();
        $this->actingAsSuperAdmin();

        // No wa_contact_id and no last_inbound_at — e.g. a Support Chat user
        // who has never given a WhatsApp number.
        $conversation = WhatsAppConversation::create(['status' => WhatsAppConversation::STATUS_OPEN]);

        Livewire::test(WhatsAppInbox::class)
            ->call('selectConversation', $conversation->id)
            ->set('reply', 'Thanks for reaching out — happy to help.')
            ->call('sendReply');

        $message = $conversation->messages()->firstOrFail();
        $this->assertSame(WhatsAppMessage::STATUS_SENT, $message->status);

        Queue::assertNothingPushed();
    }

    public function test_sending_replies_too_fast_is_rate_limited(): void
    {
        Queue::fake();
        $this->actingAsSuperAdmin();
        $conversation = $this->conversation();

        $component = Livewire::test(WhatsAppInbox::class)->call('selectConversation', $conversation->id);

        for ($i = 0; $i < 60; $i++) {
            $component->set('reply', "reply {$i}")->call('sendReply');
        }

        $this->assertSame(60, $conversation->messages()->count());

        // The 61st in the same minute is throttled — no new row.
        $component->set('reply', 'one too many')->call('sendReply');
        $this->assertSame(60, $conversation->fresh()->messages()->count());
    }
}
