<x-filament-panels::page>
    @php
        $user = auth()->user();
        $bridged = $user->whatsapp_opt_in && $user->whatsapp_number;
    @endphp

    <div class="mx-auto flex w-full max-w-2xl flex-col" style="height: calc(100vh - 16rem); min-height: 28rem;">

        @if ($bridged)
            <div class="mb-3 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Replies also go to your WhatsApp ({{ $user->whatsapp_number }}) — you can answer from there too.
            </div>
        @else
            <div class="mb-3 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Want replies on WhatsApp too?
                <a href="{{ \App\Filament\Auth\EditProfile::getUrl() }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                    Add your number in your profile.
                </a>
            </div>
        @endif

        <div wire:poll.{{ $this->pollInterval }}
             class="flex-1 space-y-3 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
            @forelse ($this->messages as $message)
                @php($out = $message->direction === 'out')
                <div class="flex {{ $out ? 'justify-start' : 'justify-end' }}">
                    <div @class([
                        'max-w-[80%] rounded-2xl px-3.5 py-2 text-sm shadow-sm',
                        'bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100' => $out,
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
                            <div class="whitespace-pre-wrap break-words">{{ $message->body }}</div>
                        @endif

                        <div @class([
                            'mt-1 text-right text-[11px]',
                            'text-gray-400' => $out,
                            'text-white/70' => ! $out,
                        ])>
                            {{ $message->created_at->format('d M, H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-gray-400">
                    No messages yet. Say hello 👋
                </p>
            @endforelse
        </div>

        <form wire:submit="send" class="mt-3 flex items-end gap-2">
            <textarea
                wire:model="draft"
                rows="1"
                placeholder="Type a message…"
                x-on:keydown.enter.prevent="$wire.send()"
                class="flex-1 resize-none rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
            ></textarea>
            <x-filament::button type="submit" icon="heroicon-m-paper-airplane">
                Send
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
