<x-mail::message>
# Your week on {{ config('app.name') }}

Hi {{ $user->first_name ?: 'there' }}, here's where things stand.

@if (! empty($data['deadlines']))
## Deadlines in the next 3 weeks

@foreach ($data['deadlines'] as $d)
- **{{ $d['title'] }}** — {{ $d['date'] }} ({{ $d['in'] }}){{ $d['scope'] ? ' · '.$d['scope'] : '' }}
@endforeach
@endif

@if (! empty($data['next_steps']))
## Next on your checklist ({{ $data['checklist_percent'] }}% done)

@foreach ($data['next_steps'] as $s)
- {{ $s }}
@endforeach
@endif

@if (($data['stalled_applications'] ?? 0) > 0)
## Applications to move forward

You have **{{ $data['stalled_applications'] }}** saved {{ \Illuminate\Support\Str::plural('program', $data['stalled_applications']) }} where you're preparing or have submitted but the application checklist isn't complete.
@endif

<x-mail::button :url="route('filament.admin.pages.my-journey')">
Open My Journey
</x-mail::button>

Curated guidance — always confirm dates and requirements on each university's official pages.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
You're getting this weekly summary because you have programs saved on {{ config('app.name') }}.
Turn it off under **Study profile → deadline reminders** in your profile.
</x-slot:subcopy>
</x-mail::message>
