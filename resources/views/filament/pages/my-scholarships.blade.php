<x-filament-panels::page>
    @php($matched = $this->getMatched())

    @unless ($this->hasShortlist())
        <x-ui.page-intro icon="heroicon-o-information-circle">
            Save programs in <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Find Universities</a>
            to see regional (DSU) scholarships for those universities' regions. Nationwide options are shown regardless.
        </x-ui.page-intro>
    @endunless

    @if ($matched['regional']->isNotEmpty())
        <div>
            <div class="ui-eyebrow mb-2">Regional (right-to-study / DSU) — matched to your shortlist</div>
            <div class="ui-grid ui-grid--2">
                @foreach ($matched['regional'] as $s)
                    @include('filament.pages.partials.scholarship-card', ['s' => $s])
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="ui-eyebrow mb-2">Nationwide</div>
        <div class="ui-grid ui-grid--2">
            @foreach ($matched['national'] as $s)
                @include('filament.pages.partials.scholarship-card', ['s' => $s])
            @endforeach
        </div>
    </div>

    <div>
        <div class="ui-eyebrow mb-2">My scholarship tracker</div>
        {{ $this->table }}
    </div>

    <p class="text-xs text-gray-400">
        Curated guidance — amounts, income thresholds and deadlines change every cycle. Always confirm on each body's official page.
    </p>
</x-filament-panels::page>
