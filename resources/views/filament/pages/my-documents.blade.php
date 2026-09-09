<x-filament-panels::page>
    @php($progress = $this->getProgress())

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium">Vault progress</span>
            <span class="text-gray-500 dark:text-gray-400">
                {{ $progress['done'] }} / {{ $progress['total'] }} documents ready
            </span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
            <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $progress['percent'] }}%"></div>
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Files are private to your account and stored securely — only you can download them.
            This is a personal tracker, not a submission channel: always upload documents through each
            university's own portal.
        </p>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
