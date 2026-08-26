<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            If your previous diploma or degree was awarded outside Italy, most universities will ask you to document
            how it fits into the Italian system before you can enrol. There are two separate routes to do that —
            this page explains both, and how to tell which one a given program actually wants.
            (This is about your qualification being recognized, not immigration — for the visa and residence permit
            process, see the <a href="{{ route('filament.admin.pages.visa-arrival') }}" class="text-primary-600 hover:underline dark:text-primary-400">Visa &amp; Arrival guide</a>.)
        </div>

        @foreach ($this->getSections() as $section)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <h2 class="text-base font-semibold">{{ $section['heading'] }}</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $section['body'] }}</p>
            </div>
        @endforeach

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
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
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
            General guidance, not a substitute for the specific admission notice (bando) of the program you're
            applying to — requirements can vary by university and by degree level. Confirm on the official sources
            above before requesting either document.
        </div>
    </div>
</x-filament-panels::page>
