<x-filament-panels::page>
    @php($states = $this->guideSectionStates())

    <div class="space-y-6">
        <x-ui.page-intro icon="heroicon-o-document-check">
            If your previous diploma or degree was awarded outside Italy, most universities will ask you to document
            how it fits into the Italian system before you can enrol. There are two separate routes to do that —
            this page explains both, and how to tell which one a given program actually wants.
            (This is about your qualification being recognized, not immigration — for the visa and residence permit
            process, see the <a href="{{ route('filament.admin.pages.visa-arrival') }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Visa &amp; Arrival guide</a>.)
        </x-ui.page-intro>

        <x-guide-progress :progress="$this->guideProgress()" />

        @foreach ($this->getSections() as $section)
            @php($sk = $section['key'])
            @php($done = $states[$sk] ?? false)
            <div @class([
                'ui-card ui-card--pad',
                'border-success-200 bg-success-50/40 dark:border-success-400/20 dark:bg-success-400/5' => $done,
            ])>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold">{{ $section['heading'] }}</h2>
                    <x-guide-section-toggle :done="$done" :section-key="$sk" />
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $section['body'] }}</p>
            </div>
        @endforeach

        <x-ui.card>
            <h2 class="text-base font-semibold">Official sources</h2>
            <ul class="mt-2 space-y-1">
                @foreach ($this->getOfficialLinks() as $label => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                            {{ $label }} &rarr;
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card class="text-xs text-gray-500 dark:text-gray-400">
            General guidance, not a substitute for the specific admission notice (bando) of the program you're
            applying to — requirements can vary by university and by degree level. Confirm on the official sources
            above before requesting either document.
        </x-ui.card>
    </div>
</x-filament-panels::page>
