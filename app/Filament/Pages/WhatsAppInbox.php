<?php

namespace App\Filament\Pages;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Staff-facing WhatsApp support console: a conversation list on the left, the
 * selected thread on the right, and a reply box that talks to the WhatsApp
 * Cloud API via a queued job. Free-form replies are only allowed inside the
 * 24h customer service window (WhatsAppConversation::withinServiceWindow());
 * outside it the box is replaced by a "send re-open template" action.
 *
 * Access is the `page_WhatsAppInbox` permission (HasPageShield) — run
 * `php artisan shield:generate` after deploy so the permission exists, then
 * grant it to the support role.
 */
class WhatsAppInbox extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $title = 'WhatsApp Inbox';

    protected static ?string $slug = 'whatsapp-inbox';

    protected static ?int $navigationSort = 35;

    protected static string $view = 'filament.pages.whatsapp-inbox';

    public ?int $activeConversationId = null;

    public string $reply = '';

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    // Same pattern as App\Filament\Pages\SystemSettings: HasPageShield gives
    // us the `page_WhatsAppInbox` permission + navigation gating, but this
    // app's Shield config has define_via_gate=false, so super_admin isn't
    // auto-granted every permission via a gate — spell it out here.
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        $superAdmin = (string) config('filament-shield.super_admin.name', 'super_admin');

        return $user->hasRole($superAdmin) || $user->can('page_WhatsAppInbox');
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = WhatsAppConversation::query()
            ->where('status', '!=', WhatsAppConversation::STATUS_CLOSED)
            ->where(fn ($q) => $q
                ->whereColumn('last_inbound_at', '>', 'last_outbound_at')
                ->orWhere(fn ($q2) => $q2->whereNotNull('last_inbound_at')->whereNull('last_outbound_at')))
            ->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    #[Computed]
    public function conversations(): Collection
    {
        return WhatsAppConversation::query()
            ->with('user')
            ->orderByRaw('COALESCE(last_inbound_at, last_outbound_at, created_at) desc')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function activeConversation(): ?WhatsAppConversation
    {
        if ($this->activeConversationId === null) {
            return null;
        }

        return WhatsAppConversation::query()
            ->with(['user', 'assignee', 'messages' => fn ($q) => $q->orderBy('created_at')])
            ->find($this->activeConversationId);
    }

    public function selectConversation(int $id): void
    {
        $this->activeConversationId = $id;
        $this->reply = '';
        unset($this->activeConversation);
    }

    public function sendReply(): void
    {
        $conversation = $this->activeConversation;
        $text = trim($this->reply);

        if ($conversation === null || $text === '') {
            return;
        }

        if (! $conversation->withinServiceWindow()) {
            Notification::make()
                ->warning()
                ->title('Outside the 24-hour window')
                ->body('WhatsApp only allows free-form replies within 24h of the customer’s last message. Send the re-open template instead.')
                ->send();

            return;
        }

        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'type' => 'text',
            'body' => $text,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'sent_by' => auth()->id(),
        ]);

        SendWhatsAppMessageJob::dispatch($message);

        $this->reply = '';
        $conversation->update(['status' => WhatsAppConversation::STATUS_PENDING]);
        unset($this->activeConversation);
    }

    public function sendReopenTemplate(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return;
        }

        $template = (string) config('services.whatsapp.reopen_template');
        $language = (string) config('services.whatsapp.reopen_template_language', 'en');

        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'type' => 'template',
            'body' => "[template: {$template}]",
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'sent_by' => auth()->id(),
        ]);

        SendWhatsAppMessageJob::dispatch($message, $template, $language);

        $conversation->update(['status' => WhatsAppConversation::STATUS_PENDING]);
        unset($this->activeConversation);

        Notification::make()->success()->title('Re-open template queued')->send();
    }

    public function assignToMe(): void
    {
        $this->activeConversation?->update(['assigned_to' => auth()->id()]);
        unset($this->activeConversation);
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, [
            WhatsAppConversation::STATUS_OPEN,
            WhatsAppConversation::STATUS_PENDING,
            WhatsAppConversation::STATUS_CLOSED,
        ], true)) {
            return;
        }

        $this->activeConversation?->update(['status' => $status]);
        unset($this->activeConversation, $this->conversations);
    }
}
