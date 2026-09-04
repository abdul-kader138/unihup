<x-filament-panels::page>
    @php
        $user = auth()->user();
        $bridged = $user->whatsapp_opt_in && $user->whatsapp_number;
    @endphp

    {{-- Same card shell, header/thread/composer structure and spacing as
         App\Filament\Pages\WhatsAppInbox — this is the same conversation
         from the other side, so it should read as the same product. --}}
    <div
        wire:poll.{{ $this->pollInterval }}
        class="flex h-[calc(100vh-13rem)] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Chat with our team
            </div>
            @if ($bridged)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Also reaches your WhatsApp ({{ $user->whatsapp_number }})
                </span>
            @else
                <a href="{{ \App\Filament\Auth\EditProfile::getUrl() }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                    Add your WhatsApp number
                </a>
            @endif
        </div>

        {{-- Messages --}}
        <div class="flex flex-1 flex-col gap-2 overflow-y-auto bg-gray-50 px-4 py-4 dark:bg-gray-950/40">
            @forelse ($this->messages as $message)
                @php($out = $message->direction === 'out')
                {{-- Mirrored from WhatsAppInbox's own left/right: there the
                     viewer is staff, so their replies sit on the right. Here
                     the viewer is the customer, so their own messages
                     (direction "in") sit on the right instead. --}}
                <div class="flex {{ $out ? 'justify-start' : 'justify-end' }}">
                    <div @class([
                        'max-w-[75%] rounded-2xl px-3 py-2 text-sm shadow-sm',
                        'bg-white text-gray-900 dark:bg-white/10 dark:text-gray-100' => $out,
                        'bg-primary-600 text-white' => ! $out,
                    ])>
                        @if ($out)
                            <div class="mb-0.5 text-xs font-semibold opacity-70">
                                {{ $message->sender?->name ?? 'Support' }}
                            </div>
                        @endif

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

                        <div class="mt-1 text-[10px] {{ $out ? 'text-gray-400' : 'text-white/70' }}">
                            {{ $message->created_at->format('d M, H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-1 items-center justify-center text-sm text-gray-400">
                    No messages yet — say hello.
                </div>
            @endforelse
        </div>

        {{-- Composer --}}
        <div class="border-t border-gray-200 p-3 dark:border-white/10">
            <form wire:submit="send" class="flex items-end gap-2">
                <textarea
                    wire:model="draft"
                    rows="2"
                    placeholder="Type a message…"
                    x-on:keydown.enter.prevent="$wire.send()"
                    class="flex-1 resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
                ></textarea>
                <x-filament::button type="submit" icon="heroicon-o-paper-airplane">Send</x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>
