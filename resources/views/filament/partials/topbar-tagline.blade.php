@php
    // Rotating, self-typing one-liner shown in the empty stretch of the topbar.
    // Two sets: a roomy one for wide screens and a terse one for medium screens
    // (below md the whole strip is hidden — see the CSS in AdminPanelProvider).
    // First line greets the signed-in user by first name.
    $firstName = trim((string) str(filament()->auth()->user()?->name ?? '')->before(' '));
    $hi = $firstName !== '' ? "Ciao {$firstName} 👋" : 'Ciao 👋';

    $full = array_values(array_filter([
        $firstName !== '' ? "Ciao {$firstName} 👋 — let's move your application forward" : null,
        'Your whole Italian university journey, in one place',
        'Find programs · compare · track every deadline',
        'From shortlist to enrolment — one clear checklist',
        'Documents, costs and reminders — all sorted for you',
    ]));

    $short = [
        $hi,
        'Your Italian university journey',
        'Find · compare · track deadlines',
        'One clear checklist, start to finish',
        'Docs, costs & reminders sorted',
    ];
@endphp

<div
    x-data="{
        full: @js($full),
        short: @js($short),
        list: [],
        text: '',
        mi: 0,
        ci: 0,
        deleting: false,
        timer: null,
        reduced: false,
        init() {
            this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.pickList();
            this._onResize = () => this.pickList();
            window.addEventListener('resize', this._onResize);

            if (this.reduced) {
                this.text = this.list[0] ?? '';
                this.timer = setInterval(() => {
                    this.mi = (this.mi + 1) % this.list.length;
                    this.text = this.list[this.mi];
                }, 5000);
                return;
            }
            this.tick();
        },
        pickList() {
            const next = window.matchMedia('(min-width: 1024px)').matches ? this.full : this.short;
            if (next === this.list) return;
            this.list = next;
            this.mi = this.mi % this.list.length;
            if (this.reduced) this.text = this.list[this.mi];
            else if (!this.deleting) this.ci = Math.min(this.ci, (this.list[this.mi] ?? '').length);
        },
        tick() {
            const current = this.list[this.mi] ?? '';
            if (!this.deleting) {
                this.ci++;
                this.text = current.slice(0, this.ci);
                if (this.ci >= current.length) {
                    this.deleting = true;
                    this.timer = setTimeout(() => this.tick(), 2400);
                    return;
                }
            } else {
                this.ci--;
                this.text = current.slice(0, Math.max(this.ci, 0));
                if (this.ci <= 0) {
                    this.deleting = false;
                    this.mi = (this.mi + 1) % this.list.length;
                    this.timer = setTimeout(() => this.tick(), 320);
                    return;
                }
            }
            this.timer = setTimeout(() => this.tick(), this.deleting ? 28 : 52);
        },
        destroy() {
            clearTimeout(this.timer);
            clearInterval(this.timer);
            window.removeEventListener('resize', this._onResize);
        },
    }"
    class="fi-topbar-tagline"
    :class="{ 'is-typing': ! reduced && ! deleting }"
    aria-hidden="true"
>
    <span class="fi-topbar-tagline__dot"></span>
    <span class="fi-topbar-tagline__text" x-text="text">{{ $short[0] }}</span>
    <span class="fi-topbar-tagline__caret" x-show="! reduced"></span>
</div>
