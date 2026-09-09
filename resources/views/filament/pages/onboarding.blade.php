<x-filament-panels::page>
    <div class="ui-hero">
        <h2 class="text-lg font-semibold tracking-tight">Let's set up your journey</h2>
        <p class="mt-0.5 max-w-2xl text-[0.8125rem] leading-relaxed text-gray-600 dark:text-gray-300">
            Three short steps. This tailors your admission checklist and the "can I apply?" hints —
            nothing here is shared with universities, and you can change it any time in your profile.
        </p>
    </div>

    <form wire:submit="submit">
        {{ $this->form }}
    </form>

    <div class="text-center">
        <a href="{{ \App\Filament\Pages\MyJourney::getUrl() }}"
           class="text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200">
            Skip for now
        </a>
    </div>
</x-filament-panels::page>
