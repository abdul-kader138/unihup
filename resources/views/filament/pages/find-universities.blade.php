<x-filament-panels::page>
    @if (! auth()->user()->preferred_subject_id)
        <div class="fi-in-entry-wrp rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            Pick a subject and degree level below to see matching programs. Click <strong>Save as my default</strong> to have this page open straight to your results next time.
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
