<x-filament-panels::page>
    @if (! auth()->user()->preferred_subject_id)
        <x-ui.page-intro icon="heroicon-o-magnifying-glass">
            Pick a subject and degree level below to see matching programs. Click <strong>Save as my default</strong>
            to have this page open straight to your results next time — and use the bookmark on any card to add it to
            <strong>My Applications</strong>.
        </x-ui.page-intro>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
