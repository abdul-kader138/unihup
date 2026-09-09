@php
    use App\Models\Setting;

    $appName = Setting::get('app_name', 'UniHup');
    $tagline = Setting::get('app_tagline', 'Find your degree program in Italy.');

    // Left brand panel: a deep slate → indigo gradient with an indigo accent.
    // The right-hand form side is a light card on a neutral grey canvas
    // (see CSS below), matching the panel's standard light-first theme.
    $start  = '15 23 42';   // slate-900
    $mid    = '30 27 75';   // indigo-950
    $end    = '49 46 129';  // indigo-900
    $accentRgb = '129 140 248'; // indigo-400 (== --primary-400)
    $accent = "rgb({$accentRgb})";

    [$ar, $ag, $ab] = explode(' ', $accentRgb);
    $a08 = "rgba({$ar},{$ag},{$ab},.08)";
    $a12 = "rgba({$ar},{$ag},{$ab},.12)";
    $a15 = "rgba({$ar},{$ag},{$ab},.15)";
    $a18 = "rgba({$ar},{$ag},{$ab},.18)";
    $a22 = "rgba({$ar},{$ag},{$ab},.22)";
    $a25 = "rgba({$ar},{$ag},{$ab},.25)";
    $a30 = "rgba({$ar},{$ag},{$ab},.30)";
    $a35 = "rgba({$ar},{$ag},{$ab},.35)";
    $a40 = "rgba({$ar},{$ag},{$ab},.40)";
    $a45 = "rgba({$ar},{$ag},{$ab},.45)";
    $a55 = "rgba({$ar},{$ag},{$ab},.55)";

    $panelBackground = "linear-gradient(145deg, rgb({$start}) 0%, rgb({$mid}) 40%, rgb({$end}) 100%)";
    $panelText    = 'rgb(255 255 255)';
    $panelMuted   = 'rgba(255,255,255,.52)';
    $badgeBg      = 'rgba(255,255,255,.06)';
    $badgeBorder  = 'rgba(255,255,255,.10)';
    $featureText  = 'rgba(255,255,255,.68)';
    $dividerColor = 'rgba(255,255,255,.08)';
    $footerText   = 'rgba(255,255,255,.32)';

    $features = [
        'Browse Italian universities by subject and degree level.',
        "Save your preferred subject so results are ready every time you sign in.",
        'Every listing links back to the official admissions source to verify.',
    ];

    // Italian tricolore
    $flagGreen = '#008C45';
    $flagWhite = '#F4F5F0';
    $flagRed   = '#CD212A';
    $tricolore = "linear-gradient(90deg, {$flagGreen} 0 33.33%, {$flagWhite} 33.33% 66.66%, {$flagRed} 66.66% 100%)";
    $tricoloreV = "linear-gradient(180deg, {$flagGreen} 0 33.33%, {$flagWhite} 33.33% 66.66%, {$flagRed} 66.66% 100%)";
@endphp

{{-- ── Layout CSS ──────────────────────────────────────────────────────────── --}}
<style>
    /* Right-hand side is a neutral light-grey canvas; the left panel carries
       its own dark gradient via inline style. */
    body.fi-body {
        background: #f1f3f5 !important;
    }

    /* ── Auth form card is always light ─────────────────────────────────────
       The login/register/reset form must NOT follow the panel's configured
       dark / sepia / midnight mode (System Settings > Panel Mode) — otherwise
       it renders as a white card with near-invisible dark-mode text. Scope
       everything to `.fi-simple-page > section` (the form card) so the dark
       left brand panel, which is a sibling, is untouched. */
    .fi-simple-main, .fi-simple-page { background: transparent !important; }
    .fi-simple-page > section {
        color-scheme: light !important;
        background: #ffffff !important;
        color: #1e293b !important;
    }
    .fi-simple-page > section :is(h1, h2),
    .fi-simple-page > section .fi-simple-header-heading { color: #0f172a !important; }
    .fi-simple-page > section :is(label, .fi-fo-field-wrp-label, .fi-fo-field-wrp-label *) { color: #334155 !important; }
    .fi-simple-page > section :is(.fi-fo-field-wrp-hint, .fi-fo-field-wrp-helper-text) { color: #64748b !important; }
    .fi-simple-page > section :is(input, select, textarea),
    .fi-simple-page > section :is(.fi-input-wrp, .fi-input) {
        background: #ffffff !important;
        color: #0f172a !important;
    }
    /* The email / password fields need a clearly visible outline. Filament's
       default is a faint 10%-opacity ring (a box-shadow) that all but
       disappears on the white card — give .fi-input-wrp a real 1px border
       plus a soft shadow, and a brand-coloured ring on focus. */
    .fi-simple-page > section .fi-input-wrp {
        border: 1px solid rgb(2 6 23 / .22) !important;
        border-radius: .5rem !important;
        box-shadow: 0 1px 2px rgb(2 6 23 / .05) !important;
        transition: border-color .15s ease, box-shadow .15s ease !important;
    }
    .fi-simple-page > section .fi-input-wrp:hover {
        border-color: rgb(2 6 23 / .34) !important;
    }
    .fi-simple-page > section .fi-input-wrp:focus-within {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 3px rgb(var(--primary-500) / .18) !important;
        --tw-ring-color: rgb(var(--primary-500) / .35) !important;
    }
    .fi-simple-page > section .fi-checkbox-input {
        background: #ffffff !important;
        border-color: rgb(2 6 23 / .3) !important;
    }
    .fi-simple-page > section .fi-checkbox-input:checked {
        background: rgb(var(--primary-600)) !important;
        border-color: rgb(var(--primary-600)) !important;
    }
    .fi-simple-page > section a { color: rgb(var(--primary-600)) !important; }
    .fi-simple-page > section .fi-btn.fi-color-primary,
    .fi-simple-page > section .fi-btn[data-color="primary"] { color: #ffffff !important; }
    .fi-simple-page > section .fi-btn.fi-color-gray {
        background: #ffffff !important;
        color: #334155 !important;
        border-color: rgb(2 6 23 / .12) !important;
    }

    @media (min-width: 1024px) {
        .auth-brand-panel {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 52% !important;
            height: 100vh !important;
            height: 100dvh !important;
            z-index: 20;
        }
        .fi-simple-main {
            /* Must stay full-bleed (not a narrower "card" width) — it's the
               outer element .fi-simple-main-ctn centers across the WHOLE
               viewport, and .auth-brand-panel renders as ITS descendant via
               the SIMPLE_PAGE_START render hook. Constraining this element's
               width fights that centering (the card ends up mid-screen, not
               in the right half) and — worse — giving it any
               transform/filter/backdrop-filter creates a CSS containing
               block that breaks .auth-brand-panel's position:fixed entirely.
               Confirmed both the hard way; the actual "card" look for the
               login form is applied below, to .fi-simple-page's real content
               child instead, which IS free to be a narrow, centered box. */
            max-width: 100vw !important;
            width: 100% !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
            background: transparent !important;
            min-height: 100vh;
        }
        .fi-simple-main.ring-1 { --tw-ring-shadow: none !important; }
        .fi-simple-page {
            min-height: 100vh;
            min-height: 100dvh;
            padding-left: 52% !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: 3rem;
            padding-bottom: 3rem;
            padding-right: 2rem;
            box-sizing: border-box;
        }
        /* .fi-simple-page also contains a few empty <form> elements Filament
           uses internally for its modal-action system (hidden until a modal
           is triggered) — targeting `section` specifically, not a broad
           :not(.auth-brand-panel) selector, avoids styling those as empty
           bordered boxes. */
        .fi-simple-page > section {
            max-width: 22rem;
            width: 100%;
            background: #ffffff;
            border: 1px solid rgb(2 6 23 / .1);
            border-radius: 1rem;
            box-shadow: 0 24px 60px -24px rgb(2 6 23 / .25), 0 2px 8px rgb(2 6 23 / .05);
            padding: 2.25rem 2rem;
            box-sizing: border-box;
        }
    }
    /* Narrow desktop split (1024–1200): 52% of a ~1100px viewport minus the
       panel's 3rem side padding barely fits the 26rem admission card, so its
       inner rows overflow. Tighten the padding and let the card + field row
       flex down. */
    @media (min-width: 1024px) and (max-width: 1200px) {
        .auth-brand-panel { padding: 2rem 2.25rem !important; }
        .abp-pass { width: 100%; }
        .abp-pass-main > div { flex-wrap: wrap; row-gap: .6rem; }
    }

    /* Short viewports (e.g. 1366×768 laptops): tighten the brand panel's
       vertical rhythm so its stacked content fits. Do NOT switch it to
       overflow: auto — the panel keeps `overflow: hidden` to clip the two
       decorative blur blobs (one sits at bottom: -12rem), and letting it
       scroll surfaces those as a phantom scrollbar on every laptop screen. */
    @media (min-width: 1024px) and (max-height: 780px) {
        .auth-brand-panel {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }
        .auth-brand-panel .abp-pass { transform: scale(.92); transform-origin: left center; }
        .fi-simple-page { padding-top: 1.5rem; padding-bottom: 1.5rem; }
    }

    /* Mobile / tablet: no split panel, just centre the light card on the grey
       canvas with room to breathe from the screen edges. */
    @media (max-width: 1023px) {
        .auth-brand-panel { display: none !important; }
        .fi-simple-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }
        .fi-simple-page > section {
            width: 100%;
            max-width: 24rem;
            margin-inline: auto;
            background: #ffffff;
            border: 1px solid rgb(2 6 23 / .1);
            border-radius: 1rem;
            box-shadow: 0 12px 32px -16px rgb(2 6 23 / .18);
            padding: 2rem 1.5rem;
            box-sizing: border-box;
        }
    }
    @media (max-width: 380px) {
        .fi-simple-page { padding: 1.25rem .75rem; }
        .fi-simple-page > section { padding: 1.5rem 1.15rem; }
    }

    /* ── Admission card illustration ────────────────────────────────────────── */
    @media (prefers-reduced-motion: no-preference) {
        .abp-pass-confirmed { animation: abp-confirmed-pulse 2.4s ease-in-out infinite; }
    }
    @keyframes abp-confirmed-pulse {
        0%, 100% { opacity: .7; }
        50%       { opacity: 1; }
    }

    .abp-pass {
        position: relative;
        width: 26rem;
        max-width: 100%;
        display: flex;
        border-radius: .75rem;
        border: 1px solid rgba(255,255,255,.1);
        background: rgba(255,255,255,.04);
        box-shadow: 0 12px 30px -8px rgba(0,0,0,.4);
        overflow: hidden;
    }
    .abp-pass-main { flex: 1; padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .55rem; }
    .abp-pass-stub {
        width: 6.5rem;
        flex-shrink: 0;
        padding: 1rem .85rem;
        display: flex;
        flex-direction: column;
        gap: .45rem;
        background: rgba(255,255,255,.03);
    }
    .abp-pass-divider {
        width: 0;
        border-left: 1.5px dashed rgba(255,255,255,.15);
        position: relative;
    }
    .abp-pass-divider::before, .abp-pass-divider::after {
        content: '';
        position: absolute;
        left: -.4rem;
        width: .8rem;
        height: .8rem;
        border-radius: 50%;
        background: rgb({{ $mid }});
    }
    .abp-pass-divider::before { top: -.4rem; }
    .abp-pass-divider::after { bottom: -.4rem; }
    .abp-pass-eyebrow { font-size: .58rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.4); }
    .abp-pass-route { font-size: 1.15rem; font-weight: 800; letter-spacing: -.01em; color: rgba(255,255,255,.92); display: flex; align-items: center; gap: .5rem; }
    .abp-pass-field-label { font-size: .56rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.38); }
    .abp-pass-field-value { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.85); }
    .abp-barcode { display: flex; align-items: flex-end; gap: 2px; height: 1.6rem; margin-top: auto; }
    .abp-barcode span { display: block; width: 2px; background: rgba(255,255,255,.35); }
</style>

<div
    class="auth-brand-panel"
    style="
        position: relative;
        overflow: hidden;
        background: {{ $panelBackground }};
        color: {{ $panelText }};
        display: flex;
        flex-direction: column;
        padding: 2.5rem 3rem;
    "
>
    {{-- ── Italian tricolore edge ─────────────────────────────────────────── --}}
    <div style="position:absolute;top:0;bottom:0;left:0;width:6px;background:{{ $tricoloreV }};pointer-events:none;z-index:2;"></div>

    {{-- ── Background layers ──────────────────────────────────────────────── --}}

    <div style="position:absolute;inset:0;background-image:radial-gradient(circle, {{ $a18 }} 1px, transparent 1px);background-size:30px 30px;pointer-events:none;"></div>

    <div style="position:absolute;inset:0;opacity:.025;background-image:url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E&quot;);background-size:200px 200px;pointer-events:none;"></div>

    <div style="position:absolute;top:-10rem;right:-5rem;width:32rem;height:32rem;border-radius:9999px;background:{{ $accent }};opacity:.13;filter:blur(90px);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-12rem;left:-8rem;width:38rem;height:38rem;border-radius:9999px;background:{{ $accent }};opacity:.09;filter:blur(120px);pointer-events:none;"></div>

    {{-- ── Content ─────────────────────────────────────────────────────────── --}}
    <div style="position:relative;z-index:1;display:flex;flex-direction:column;height:100%;gap:0;">

        {{-- ── Logo row ────────────────────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;border-radius:.625rem;background:{{ $accent }};font-size:1.1rem;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 4px 16px {{ $a45 }};">
                {{ strtoupper(substr($appName, 0, 1)) }}
            </div>
            <span style="font-size:1.1rem;font-weight:700;letter-spacing:-.02em;color:{{ $panelText }};">{{ $appName }}</span>
            <span style="margin-left:auto;display:inline-flex;align-items:center;gap:.5rem;font-size:.62rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.6);background:{{ $badgeBg }};border:1px solid {{ $badgeBorder }};padding:.28rem .6rem .28rem .5rem;border-radius:9999px;">
                <span style="width:1.15rem;height:.78rem;border-radius:2px;background:{{ $tricolore }};box-shadow:0 0 0 1px rgba(255,255,255,.15);flex-shrink:0;"></span>
                Italy
            </span>
        </div>

        {{-- ── Middle: illustration + text ─────────────────────────────────── --}}
        <div style="flex:1;display:flex;flex-direction:column;justify-content:center;gap:1.25rem;padding:.5rem 0;">

            {{-- ── Admission info card ────────────────────────────────────────── --}}
            <div style="width:100%;display:flex;justify-content:center;">
                <div class="abp-pass">
                    <div class="abp-pass-main">
                        <div class="abp-pass-eyebrow">Degree Program</div>
                        <div class="abp-pass-route">
                            Computer Science
                            <svg viewBox="0 0 16 16" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity:.5;">
                                <path d="M2 8h11m0 0-4-4m4 4-4 4" stroke="{{ $accent }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Master's
                        </div>
                        <div style="display:flex;gap:1.75rem;margin-top:.15rem;">
                            <div>
                                <div class="abp-pass-field-label">University</div>
                                <div class="abp-pass-field-value">Politecnico di Milano</div>
                            </div>
                            <div>
                                <div class="abp-pass-field-label">City</div>
                                <div class="abp-pass-field-value">Milan</div>
                            </div>
                            <div>
                                <div class="abp-pass-field-label">Language</div>
                                <div class="abp-pass-field-value">English</div>
                            </div>
                        </div>
                    </div>
                    <div class="abp-pass-divider"></div>
                    <div class="abp-pass-stub">
                        <div class="abp-pass-eyebrow">Admission</div>
                        <div class="abp-pass-field-value" style="font-size:1rem;">Open</div>
                        <span class="abp-pass-confirmed" style="font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#34d399;">
                            ● Verified link
                        </span>
                        <div class="abp-barcode">
                            @foreach ([3,1,2,1,3,2,1,1,2,3,1,2,1,3,1,2,1,1,3,2] as $w)
                                <span style="width:{{ $w }}px;height:{{ $w === 3 ? '100%' : ($w === 2 ? '75%' : '55%') }};"></span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Headline ────────────────────────────────────────────────── --}}
            <div style="display:flex;flex-direction:column;gap:.55rem;">
                <h2 style="font-size:clamp(1.8rem,3vw,2.4rem);font-weight:800;letter-spacing:-.04em;line-height:1.13;color:{{ $panelText }};margin:0;">
                    Study in <span style="position:relative;color:{{ $accent }};white-space:nowrap;">Italy<span style="position:absolute;left:0;right:0;bottom:-.28rem;height:.28rem;border-radius:2px;background:{{ $tricolore }};"></span></span>
                </h2>
                <p style="margin:.35rem 0 0;font-size:.9rem;color:{{ $panelMuted }};line-height:1.6;max-width:28rem;">{{ $tagline }}</p>
            </div>

            {{-- ── Divider ─────────────────────────────────────────────────── --}}
            <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:1.5rem;height:3px;border-radius:2px;background:{{ $tricolore }};flex-shrink:0;"></div>
                <div style="flex:1;height:1px;background:linear-gradient(to right, {{ $dividerColor }}, transparent);"></div>
            </div>

            {{-- ── Numbered features ───────────────────────────────────────── --}}
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                @foreach ($features as $i => $feature)
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <span style="font-size:.68rem;font-weight:700;letter-spacing:.06em;color:{{ $accent }};opacity:.75;font-variant-numeric:tabular-nums;min-width:1.6rem;flex-shrink:0;">0{{ $i + 1 }}</span>
                        <div style="width:2rem;height:1px;background:{{ $dividerColor }};flex-shrink:0;"></div>
                        <span style="font-size:.875rem;color:{{ $featureText }};line-height:1.45;">{{ $feature }}</span>
                    </div>
                @endforeach
            </div>

        </div>

        {{-- ── Footer ───────────────────────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;gap:.5rem;font-size:.72rem;color:{{ $footerText }};flex-shrink:0;">
            <span style="display:inline-flex;gap:.22rem;align-items:center;">
                <span style="width:.38rem;height:.38rem;border-radius:50%;background:{{ $accent }};opacity:.6;display:inline-block;"></span>
                <span style="width:.38rem;height:.38rem;border-radius:50%;background:{{ $accent }};opacity:.35;display:inline-block;"></span>
                <span style="width:.38rem;height:.38rem;border-radius:50%;background:{{ $accent }};opacity:.16;display:inline-block;"></span>
            </span>
            {{ $appName }}
        </div>

    </div>
</div>
