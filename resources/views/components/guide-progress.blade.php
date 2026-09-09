@props(['progress'])

@if (($progress['total'] ?? 0) > 0)
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium">Your progress through this guide</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $progress['done'] }} / {{ $progress['total'] }} read</span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
            <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>
@endif
