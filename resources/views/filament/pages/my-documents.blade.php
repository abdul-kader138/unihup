<x-filament-panels::page>
    @php($progress = $this->getProgress())

    <x-ui.card>
        <x-ui.eyebrow>
            Vault progress
            <x-slot:action>
                <span class="text-gray-500 dark:text-gray-400">{{ $progress['done'] }} / {{ $progress['total'] }} ready</span>
            </x-slot:action>
        </x-ui.eyebrow>
        <x-ui.progress :value="$progress['percent']" class="mt-3" />
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Files are private to your account and stored securely — only you can download them. This is a personal
            tracker, not a submission channel: always upload documents through each university's own portal.
        </p>
    </x-ui.card>

    {{ $this->table }}
</x-filament-panels::page>
