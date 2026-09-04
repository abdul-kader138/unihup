<x-filament-panels::page>
    @php
        // NB: this Blade runs inside a component-slot closure, so `use`
        // imports aren't possible here — model constants are referenced
        // fully-qualified.
        $conversations = $this->conversations;
        $active = $this->activeConversation;

        $statusClasses = [
            'open' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
            'pending' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
            'closed' => 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300',
        ];
    @endphp

    <style>
        /* Keep the inbox usable when the separately-built Filament theme is stale. */
        .whatsapp-inbox-layout {
            flex-direction: column;
        }

        .whatsapp-inbox-conversations {
            width: 100%;
            max-height: 14rem;
            border-right-width: 0;
            border-bottom-width: 1px;
        }

        @media (min-width: 40rem) {
            .whatsapp-inbox-layout {
                flex-direction: row;
            }

            .whatsapp-inbox-conversations {
                width: 18rem;
                max-height: none;
                border-right-width: 1px;
                border-bottom-width: 0;
            }
        }
    </style>

    <div
        wire:poll.{{ $this->pollInterval }}
        class="whatsapp-inbox-layout flex h-[calc(100dvh-9rem)] min-h-[32rem] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900 md:h-[calc(100vh-13rem)] md:min-h-0 md:flex-row"
    >
        {{-- ── Conversation list ─────────────────────────────────────────── --}}
        <aside class="whatsapp-inbox-conversations flex max-h-56 w-full shrink-0 flex-col border-b border-gray-200 dark:border-white/10 md:max-h-none md:w-72 md:border-b-0 md:border-r">
            <div class="border-b border-gray-200 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                Conversations
            </div>

            <div class="flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/5">
                @forelse ($conversations as $conversation)
                    @php
                        $waiting = $conversation->last_inbound_at
                            && (! $conversation->last_outbound_at || $conversation->last_inbound_at->gt($conversation->last_outbound_at));
                    @endphp
                    <button
                        type="button"
                        wire:click="selectConversation({{ $conversation->id }})"
                        @class([
                            'flex w-full flex-col gap-0.5 px-4 py-3 text-left text-sm transition',
                            'hover:bg-gray-50 dark:hover:bg-white/5' => $active?->id !== $conversation->id,
                            'bg-indigo-50 dark:bg-indigo-500/20' => $active?->id === $conversation->id,
                        ])
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span @class([
                                'truncate font-medium',
                                'text-indigo-950 dark:text-indigo-50' => $active?->id === $conversation->id,
                                'text-gray-900 dark:text-gray-100' => $active?->id !== $conversation->id,
                            ])>
                                {{ $conversation->user?->name ?: ($conversation->wa_contact_name ?: '+' . $conversation->wa_contact_id) }}
                            </span>
                            @if ($waiting)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-primary-500"></span>
                            @endif
                        </div>
                        <div @class([
                            'flex items-center justify-between gap-2 text-xs',
                            'text-indigo-900/70 dark:text-indigo-100/75' => $active?->id === $conversation->id,
                            'text-gray-500 dark:text-gray-400' => $active?->id !== $conversation->id,
                        ])>
                            <span class="truncate">+{{ $conversation->wa_contact_id }}</span>
                            <span>{{ optional($conversation->last_inbound_at ?? $conversation->updated_at)->diffForHumans(short: true) }}</span>
                        </div>
                        <span @class([
                            'mt-0.5 w-fit rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase',
                            $active?->id === $conversation->id
                                ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-400/20 dark:text-indigo-100'
                                : ($statusClasses[$conversation->status] ?? ''),
                        ])>
                            {{ $conversation->status }}
                        </span>
                    </button>
                @empty
                    <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No conversations yet.</p>
                @endforelse
            </div>

            @if ($conversations->hasPages())
                <div class="shrink-0 border-t border-gray-200 px-2 py-1.5 dark:border-white/10">
                    <x-filament::pagination :paginator="$conversations" />
                </div>
            @endif
        </aside>

        {{-- ── Active thread ─────────────────────────────────────────────── --}}
        <section class="flex min-w-0 flex-1 flex-col">
            @if ($active === null)
                <div class="flex flex-1 items-center justify-center text-sm text-gray-400">
                    Select a conversation to view the thread.
                </div>
            @else
                {{-- Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $active->user?->name ?: ($active->wa_contact_name ?: 'Unknown') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $active->wa_contact_id ? '+'.$active->wa_contact_id : 'Portal only — no WhatsApp number' }}
                            @if ($active->assignee) · assigned to {{ $active->assignee->name }} @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <x-filament::button size="xs" color="gray" wire:click="assignToMe">Assign to me</x-filament::button>
                        @if ($active->status === 'closed')
                            <x-filament::button size="xs" color="gray" wire:click="setStatus('open')">Re-open</x-filament::button>
                        @else
                            <x-filament::button size="xs" color="gray" wire:click="setStatus('closed')">Close</x-filament::button>
                        @endif
                    </div>
                </div>

                {{-- Messages --}}
                <div class="flex flex-1 flex-col gap-2 overflow-y-auto bg-gray-50 px-4 py-4 dark:bg-gray-950">
                    @if ($this->hasMoreMessages)
                        <button type="button" wire:click="loadEarlierMessages"
                            class="mx-auto mb-1 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            Load earlier messages
                        </button>
                    @endif

                    @foreach ($this->activeMessages as $message)
                        @php $out = $message->direction === 'out'; @endphp
                        <div class="flex {{ $out ? 'justify-end' : 'justify-start' }}">
                            <div @class([
                                'max-w-[90%] rounded-2xl px-3 py-2 text-sm shadow-sm md:max-w-[75%]',
                                'bg-primary-600 text-white' => $out,
                                'bg-white text-gray-900 dark:bg-gray-700 dark:text-gray-100' => ! $out,
                            ])>
                                @if ($message->media_path)
                                    @if (str_starts_with((string) $message->media_mime, 'image/'))
                                        <img src="{{ route('whatsapp.media', $message) }}" alt="attachment" class="mb-1 max-h-64 rounded-lg" />
                                    @else
                                        <a href="{{ route('whatsapp.media', $message) }}" target="_blank" class="mb-1 flex items-center gap-1 underline">
                                            <x-heroicon-o-paper-clip class="h-4 w-4" /> {{ $message->media_mime ?: 'attachment' }}
                                        </a>
                                    @endif
                                @endif

                                @if (filled($message->body))
                                    <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                                @endif

                                <div class="mt-1 flex items-center gap-1 text-[10px] {{ $out ? 'text-white/70' : 'text-gray-400' }}">
                                    <span>{{ $message->created_at->format('d M, H:i') }}</span>
                                    @if ($out)
                                        <span>· {{ $message->status }}</span>
                                        @if ($message->status === 'failed' && $message->error)
                                            <span class="text-red-300">— {{ $message->error }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Composer --}}
                <div class="border-t border-gray-200 p-3 dark:border-white/10">
                    @if (! $active->wa_contact_id || $active->withinServiceWindow())
                        <form wire:submit="sendReply" class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-end">
                            <textarea
                                wire:model="reply"
                                rows="2"
                                placeholder="Type a reply…"
                                class="flex-1 resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
                            ></textarea>
                            <x-filament::button type="submit" icon="heroicon-o-paper-airplane" class="sm:shrink-0">Send</x-filament::button>
                        </form>
                    @else
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                            <span>The 24-hour reply window has closed. Send the approved template to re-open the chat.</span>
                            <x-filament::button size="sm" color="warning" wire:click="sendReopenTemplate">Send re-open template</x-filament::button>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
