@php($when = $offsetDays === 1 ? 'tomorrow' : "in {$offsetDays} days")

<x-mail::message>
# {{ $deadlines->count() === 1 ? 'A deadline is coming up' : 'Deadlines coming up' }}

Hi {{ $user->first_name ?: 'there' }},

@if ($deadlines->count() === 1)
The following deadline on your list is **due {{ $when }}**:
@else
These deadlines on your list are **due {{ $when }}**:
@endif

@foreach ($deadlines as $deadline)
<x-mail::panel>
**{{ $deadline->title }}**
{{ $deadline->due_at->format('l, j F Y') }} · {{ $deadline->categoryLabel() }} · {{ $deadline->scopeName() }}

@if ($deadline->description){{ $deadline->description }}@endif
@if ($deadline->url)

<x-mail::button :url="$deadline->url">Open the official page</x-mail::button>
@endif
</x-mail::panel>
@endforeach

<x-mail::button :url="route('filament.admin.pages.my-deadlines')">
View all my deadlines
</x-mail::button>

Dates are curated guidance — always confirm on each university's and consulate's own pages.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
You're getting this because you have programs saved on {{ config('app.name') }}. You can turn these
reminders off under **Study profile → deadline reminders** in your profile.
</x-slot:subcopy>
</x-mail::message>
