<?php

namespace App\Filament\Pages;

use App\Events\WhatsAppMessageCreated;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

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
    use HasPageShield, WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $title = 'WhatsApp Inbox';

    protected static ?string $slug = 'whatsapp-inbox';

    protected static ?int $navigationSort = 35;

    protected static string $view = 'filament.pages.whatsapp-inbox';

    public ?int $activeConversationId = null;

    public string $reply = '';

    // Same "show N, grow on demand" pattern as SupportChat — a thread with a
    // long history shouldn't be fetched in full on every wire:poll tick.
    public int $messagesToShow = 40;

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

    /**
     * Live push on any new message when Reverb is configured; the wire:poll
     * fallback in the view covers the plain-database setup.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-private:whatsapp.inbox,.message.created' => '$refresh'];
    }

    #[Computed]
    public function pollInterval(): string
    {
        return config('broadcasting.default') === 'null' ? '6s' : '30s';
    }

    /**
     * Runs on every panel page load for every staff member with access (the
     * badge callback fires wherever the nav item is rendered, not just on
     * this page) — cached briefly so it's one query per 15s across all of
     * them rather than one per request.
     */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Cache::remember('whatsapp-inbox:badge-count', 15, function () {
            return WhatsAppConversation::query()
                ->where('status', '!=', WhatsAppConversation::STATUS_CLOSED)
                ->where(fn ($q) => $q
                    ->whereColumn('last_inbound_at', '>', 'last_outbound_at')
                    ->orWhere(fn ($q2) => $q2->whereNotNull('last_inbound_at')->whereNull('last_outbound_at')))
                ->count();
        });

        return $waiting > 0 ? (string) $waiting : null;
    }

    /** Called wherever a conversation's waiting-state might change, so the badge doesn't wait out its own TTL. */
    public static function forgetBadgeCache(): void
    {
        Cache::forget('whatsapp-inbox:badge-count');
    }

    /**
     * simplePaginate rather than paginate(): no total-row COUNT query, which
     * matters once this table has thousands of conversations behind it — a
     * prev/next footer is all a narrow sidebar list needs anyway.
     */
    #[Computed]
    public function conversations(): Paginator
    {
        return WhatsAppConversation::query()
            ->with('user')
            ->orderByRaw('COALESCE(last_inbound_at, last_outbound_at, created_at) desc')
            ->simplePaginate(20);
    }

    #[Computed]
    public function activeConversation(): ?WhatsAppConversation
    {
        if ($this->activeConversationId === null) {
            return null;
        }

        return WhatsAppConversation::query()
            ->with(['user', 'assignee'])
            ->find($this->activeConversationId);
    }

    /** The most recent $messagesToShow messages in the active thread, oldest first. */
    #[Computed]
    public function activeMessages(): Collection
    {
        if ($this->activeConversationId === null) {
            return collect();
        }

        return WhatsAppMessage::where('conversation_id', $this->activeConversationId)
            ->latest('created_at')
            ->limit($this->messagesToShow)
            ->get()
            ->reverse()
            ->values();
    }

    /** Cheap proxy (see SupportChat::hasMoreMessages()) rather than a COUNT query on every poll. */
    #[Computed]
    public function hasMoreMessages(): bool
    {
        return $this->activeMessages->count() >= $this->messagesToShow;
    }

    public function loadEarlierMessages(): void
    {
        $this->messagesToShow += 40;
        unset($this->activeMessages, $this->hasMoreMessages);
    }

    public function selectConversation(int $id): void
    {
        $this->activeConversationId = $id;
        $this->reply = '';
        $this->messagesToShow = 40;
        unset($this->activeConversation, $this->activeMessages, $this->hasMoreMessages);
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

        if ($this->tooManySends()) {
            return;
        }

        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'type' => 'text',
            'body' => $text,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'sent_by' => auth()->id(),
        ]);

        WhatsAppMessageCreated::dispatch($message);
        SendWhatsAppMessageJob::dispatch($message);

        $this->reply = '';
        $conversation->update(['status' => WhatsAppConversation::STATUS_PENDING]);
        self::forgetBadgeCache();
        unset($this->activeConversation, $this->activeMessages, $this->hasMoreMessages);
    }

    /** 60 sends/minute per staff member — a real support agent, not a runaway loop. */
    private function tooManySends(): bool
    {
        $key = 'whatsapp-inbox-send:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            Notification::make()->warning()->title('Sending too fast — please slow down')->send();

            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    public function sendReopenTemplate(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null || $this->tooManySends()) {
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

        WhatsAppMessageCreated::dispatch($message);
        SendWhatsAppMessageJob::dispatch($message, $template, $language);

        $conversation->update(['status' => WhatsAppConversation::STATUS_PENDING]);
        self::forgetBadgeCache();
        unset($this->activeConversation, $this->activeMessages, $this->hasMoreMessages);

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
        self::forgetBadgeCache();
        unset($this->activeConversation, $this->conversations);
    }
}
