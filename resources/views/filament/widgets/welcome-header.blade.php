<x-filament-widgets::widget>
    <div class="ui-hero flex items-center gap-4">
        <img
            src="{{ filament()->getUserAvatarUrl($this->getUser()) }}"
            alt="{{ $this->getUser()?->name }}"
            class="h-14 w-14 rounded-full object-cover ring-2 ring-white/60 dark:ring-white/10"
        />
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $this->getGreeting() }}, {{ $this->getUser()?->name }}
            </h2>
            <div class="mt-1 flex flex-wrap gap-1.5">
                @foreach ($this->getUser()?->roles->pluck('name') ?? [] as $role)
                    <span class="inline-flex items-center rounded-md bg-primary-500/10 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                        {{ str($role)->replace('_', ' ')->title() }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
