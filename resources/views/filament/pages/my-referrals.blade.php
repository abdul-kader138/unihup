<x-filament-panels::page>
    @php($link = $this->getReferralLink())
    @php($friends = $this->getReferredUsers())

    <div class="ui-hero">
        <h2 class="text-lg font-semibold tracking-tight">Invite friends to UniHup</h2>
        <p class="mt-0.5 max-w-2xl text-[0.8125rem] leading-relaxed text-gray-600 dark:text-gray-300">
            Share your link — when someone joins through it, you'll get a notification and they'll show up in your list below.
        </p>
    </div>

    <x-ui.card>
        <x-ui.eyebrow>Your referral link</x-ui.eyebrow>
        <div
            x-data="{
                copied: false,
                copy() {
                    navigator.clipboard.writeText($refs.link.value).then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    });
                },
            }"
            class="mt-2.5 flex items-center gap-2"
        >
            <input
                x-ref="link"
                type="text"
                readonly
                value="{{ $link }}"
                class="w-full min-w-0 flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                onclick="this.select()"
            >
            <button
                type="button"
                x-on:click="copy()"
                class="shrink-0 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-500"
            >
                <span x-show="! copied">Copy</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </button>
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-ui.eyebrow>
            Friends who joined
            <x-slot:action>
                <span class="text-gray-500 dark:text-gray-400">{{ $friends->count() }} {{ \Illuminate\Support\Str::plural('friend', $friends->count()) }}</span>
            </x-slot:action>
        </x-ui.eyebrow>

        @if ($friends->isEmpty())
            <x-ui.empty-state
                icon="heroicon-o-user-plus"
                heading="No one yet"
                description="Share your link above — friends who register through it will show up here."
                class="mt-3"
            />
        @else
            <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($friends as $friend)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="font-medium">{{ $friend->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Joined {{ $friend->created_at->format('j M Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</x-filament-panels::page>
