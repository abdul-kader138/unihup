@php
    // A continuously scrolling marquee shown in the empty stretch of the topbar.
    // The list is rendered TWICE inside .fi-topbar-tagline__track — the CSS
    // animation shifts the track by -50%, so the second copy sits exactly where
    // the first began and the loop is seamless. First line greets the signed-in
    // user by first name. Styling + animation live in AdminPanelProvider's
    // STYLES_AFTER render hook.
    $firstName = trim((string) str(filament()->auth()->user()?->name ?? '')->before(' '));

    $messages = array_values(array_filter([
        $firstName !== ''
            ? "Ciao {$firstName} 👋 — let's move your application forward"
            : 'Ciao 👋 — let\'s move your application forward',
        'Your whole Italian university journey, in one place',
        'Find programs · compare · track every deadline',
        'From shortlist to enrolment — one clear checklist',
        'Documents, costs and reminders — all sorted for you',
    ]));
@endphp

<div class="fi-topbar-tagline" aria-hidden="true">
    <span class="fi-topbar-tagline__dot"></span>

    <div class="fi-topbar-tagline__viewport">
        <div class="fi-topbar-tagline__track">
            @foreach (array_merge($messages, $messages) as $message)
                <span class="fi-topbar-tagline__item">{{ $message }}</span>
                <span class="fi-topbar-tagline__sep">◆</span>
            @endforeach
        </div>
    </div>
</div>
