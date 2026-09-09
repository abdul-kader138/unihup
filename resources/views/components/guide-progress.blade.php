@props(['progress'])

@if (($progress['total'] ?? 0) > 0)
    <x-ui.card>
        <x-ui.eyebrow>
            Your progress through this guide
            <x-slot:action>
                <span class="text-gray-500 dark:text-gray-400">{{ $progress['done'] }} / {{ $progress['total'] }} read</span>
            </x-slot:action>
        </x-ui.eyebrow>
        <x-ui.progress :value="$progress['percent']" class="mt-3" />
    </x-ui.card>
@endif
