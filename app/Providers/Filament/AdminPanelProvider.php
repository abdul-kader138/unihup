<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Filament\Auth\EmailVerificationPrompt;
use App\Filament\Auth\Login;
use App\Filament\Auth\Register;
use App\Filament\Auth\ResetPassword;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MyJourney;
use App\Http\Middleware\RedirectNonAdminsFromDashboard;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    // ── Available admin color themes ──────────────────────────────────────────
    public static array $themes = [
        'indigo' => ['label' => 'Indigo (default)', 'color' => 'indigo'],
        'terracotta' => ['label' => 'Terracotta', 'color' => 'terracotta'],
        'amber' => ['label' => 'Amber Gold', 'color' => 'amber'],
        'emerald' => ['label' => 'Emerald',    'color' => 'emerald'],
        'rose' => ['label' => 'Rose',       'color' => 'rose'],
        'violet' => ['label' => 'Violet',     'color' => 'violet'],
        'sky' => ['label' => 'Sky Blue',   'color' => 'sky'],
        'teal' => ['label' => 'Teal',       'color' => 'teal'],
        'orange' => ['label' => 'Orange',     'color' => 'orange'],
        'slate' => ['label' => 'Slate',      'color' => 'slate'],
        'gray' => ['label' => 'Gray',       'color' => 'gray'],
        'blue' => ['label' => 'Blue',       'color' => 'blue'],
        'cyan' => ['label' => 'Cyan',       'color' => 'cyan'],
        'purple' => ['label' => 'Purple',     'color' => 'purple'],
        'pink' => ['label' => 'Pink',       'color' => 'pink'],
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->theme(asset('css/filament/admin/theme.css'))
            ->homeUrl(fn () => Dashboard::canAccess() ? Dashboard::getUrl() : MyJourney::getUrl())
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->emailVerification(EmailVerificationPrompt::class)
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName(fn () => Setting::get('app_name', 'UniHup'))
            ->brandLogo(fn () => self::resolveBrandLogoUrl())
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => self::resolveFaviconUrl())
            ->colors([
                'primary' => self::resolveThemeColor(),
                // Crisp cool-neutral greys for the chrome (sidebar, text, borders).
                'gray' => Color::Slate,
            ])
            ->defaultThemeMode(self::resolveDefaultThemeMode())
            ->darkMode(...array_values(self::resolveDarkModeArgs()))
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => self::resolveAdminPanelModeStyles(),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => self::resolvePwaHead().self::resolveEchoScript(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): View => view('filament.partials.topbar-tagline'),
            )
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->navigationGroups([
                // No group icons — Filament suppresses per-item icons and shows a
                // connector dot instead whenever a group has an icon. Plain-label
                // groups let every nav item keep its own icon.
                NavigationGroup::make('My Journey'),
                NavigationGroup::make('Guides'),
                NavigationGroup::make('Universities'),
                NavigationGroup::make('Administration')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn () => view('components.auth-brand-panel'),
                scopes: [
                    Login::class,
                    Register::class,
                    RequestPasswordReset::class,
                    ResetPassword::class,
                    EmailVerificationPrompt::class,
                ],
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => self::resolveGoogleAuthButton(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn () => self::resolveGoogleAuthButton(),
            )
            ->authMiddleware([
                Authenticate::class,
                RedirectNonAdminsFromDashboard::class,
            ]);
    }

    // Wrapped in try/catch throughout: this provider boots on every artisan
    // command, including the very first `migrate` on a fresh install, before
    // the settings table exists.

    /**
     * Curated warm terracotta ramp — a bit earthy, reads well as buttons in
     * light mode and as accents on the deep slate-blue dark surface. Values
     * are "r g b" triples, the shape Filament expects.
     */
    public const TERRACOTTA = [
        50 => '253 245 240',
        100 => '250 231 219',
        200 => '244 205 179',
        300 => '236 173 133',
        400 => '227 135 88',
        500 => '213 103 58',
        600 => '193 82 46',
        700 => '160 63 38',
        800 => '129 53 38',
        900 => '106 47 35',
        950 => '58 22 16',
    ];

    protected static function resolveThemeColor(): array
    {
        $colorMap = [
            'terracotta' => self::TERRACOTTA,
            'indigo' => Color::Indigo,
            'amber' => Color::Amber,
            'emerald' => Color::Emerald,
            'rose' => Color::Rose,
            'violet' => Color::Violet,
            'sky' => Color::Sky,
            'teal' => Color::Teal,
            'orange' => Color::Orange,
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'blue' => Color::Blue,
            'cyan' => Color::Cyan,
            'purple' => Color::Purple,
            'pink' => Color::Pink,
        ];

        try {
            $theme = Setting::get('admin_theme', 'indigo');
        } catch (\Throwable) {
            $theme = 'indigo';
        }

        return $colorMap[$theme] ?? Color::Indigo;
    }

    // ── Panel dark-mode resolution ────────────────────────────────────────────
    public static function resolveNativeThemeModeKey(?string $mode = null): string
    {
        return match ($mode) {
            'light', 'sepia' => 'light',
            'dark', 'high_contrast', 'midnight' => 'dark',
            'system' => 'system',
            default => 'light',
        };
    }

    protected static function resolveDefaultThemeMode(): ThemeMode
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'light');
        } catch (\Throwable) {
            $mode = 'light';
        }

        return match (self::resolveNativeThemeModeKey($mode)) {
            'dark' => ThemeMode::Dark,
            'system' => ThemeMode::System,
            default => ThemeMode::Light,
        };
    }

    protected static function resolveDarkModeArgs(): array
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'light');
        } catch (\Throwable) {
            $mode = 'light';
        }

        return match (self::resolveNativeThemeModeKey($mode)) {
            'dark' => ['condition' => true,  'isForced' => true],
            'system' => ['condition' => true,  'isForced' => false],
            default => ['condition' => false, 'isForced' => false],
        };
    }

    // ── Extra CSS injected after Filament styles (panel mode overrides) ───────
    protected static function resolveAdminPanelModeStyles(): string
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'light');
        } catch (\Throwable) {
            $mode = 'light';
        }

        $custom = match ($mode) {
            'high_contrast' => <<<'CSS'
<style>
    .fi-body {
        background: #020617 !important;
        color: #f8fafc !important;
        color-scheme: dark;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: #020617 !important;
        border-color: rgba(148,163,184,.22) !important;
    }
    .fi-sidebar-item-label, .fi-sidebar-group-label { color: #e2e8f0 !important; }
</style>
CSS,
            'sepia' => <<<'CSS'
<style>
    .fi-body {
        background: linear-gradient(180deg, #f7efe4 0%, #efe3d2 100%) !important;
        color: #4b3621 !important;
        color-scheme: light;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: #f9f2e8 !important;
        border-color: rgba(120,86,56,.16) !important;
    }
</style>
CSS,
            'midnight' => <<<'CSS'
<style>
    .fi-body {
        background: linear-gradient(180deg, #020617 0%, #0f172a 55%, #111827 100%) !important;
        color: #e5e7eb !important;
        color-scheme: dark;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: rgba(15,23,42,.96) !important;
        border-color: rgba(96,165,250,.18) !important;
    }
</style>
CSS,
            default => '',
        };

        // Always applied, regardless of theme mode:
        //  - hide the built-in theme switcher (managed via System Settings)
        //  - left sidebar polish: tighter group labels, rounded items with a
        //    smooth hover, and a brand-accented active state with a left bar.
        //    Scoped to .fi-sidebar and uses Filament's own --primary-* vars so
        //    it follows the configured brand colour and both light/dark modes.
        $base = <<<'CSS'
<style>
    .fi-theme-switcher { display: none !important; }

    /* ══ Sidebar — light, in the same surface family as the content ═════
       Near-white with a crisp border so it reads as one cohesive UI, not a
       dark rail bolted onto a light page. */
    .fi-sidebar,
    .fi-sidebar .fi-sidebar-header,
    .fi-sidebar .fi-sidebar-nav {
        background: #fbfbfc !important;
    }
    .fi-sidebar { border-inline-end: 1px solid rgb(var(--gray-950) / .08) !important; }
    .fi-sidebar-header { border-color: rgb(var(--gray-950) / .07) !important; }
    /* Pin the sidebar so it always fills the viewport height (Filament leaves
       it position: relative here). */
    .fi-sidebar {
        position: sticky !important;
        top: 0;
        align-self: flex-start;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .fi-sidebar-nav { gap: .1rem; padding: .625rem .625rem 1rem; }
    .fi-sidebar-group + .fi-sidebar-group { margin-top: 1rem; }
    .fi-sidebar-group-label {
        padding-inline: .5rem;
        margin-bottom: .15rem;
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: rgb(var(--gray-400));
    }

    /* Filament draws a dot + connector line for iconless grouped items — hide
       it; items read as clean text links under their section header. */
    .fi-sidebar-item-grouped-border { display: none !important; }
    .fi-sidebar-nav ul, .fi-sidebar-group-items { list-style: none !important; }
    .fi-sidebar-group:has(.fi-sidebar-group-label) .fi-sidebar-item-button { padding-inline-start: .875rem; }

    .fi-sidebar-item-button {
        position: relative;
        gap: .6rem;
        padding: .5rem .625rem;
        border-radius: .5rem;
        font-size: .875rem;
        font-weight: 500;
        color: rgb(var(--gray-600));
        transition: background-color .14s ease, color .14s ease;
    }
    .fi-sidebar-item-button:hover {
        background-color: rgb(var(--gray-950) / .045);
        color: rgb(var(--gray-900));
    }
    .fi-sidebar-item-icon {
        width: 1.15rem; height: 1.15rem;
        color: rgb(var(--gray-400));
        transition: color .14s ease;
    }
    .fi-sidebar-item-button:hover .fi-sidebar-item-icon { color: rgb(var(--gray-500)); }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
    .fi-sidebar-item-button.fi-active {
        background-color: rgb(var(--primary-500) / .11);
        color: rgb(var(--primary-700));
        font-weight: 600;
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-button::after,
    .fi-sidebar-item-button.fi-active::after {
        content: "";
        position: absolute;
        inset-inline-start: -.625rem;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 1.25rem;
        border-radius: 0 3px 3px 0;
        background-color: rgb(var(--primary-500));
    }
    .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon {
        color: rgb(var(--primary-600));
    }

    :is(.dark) .fi-sidebar,
    :is(.dark) .fi-sidebar .fi-sidebar-header,
    :is(.dark) .fi-sidebar .fi-sidebar-nav { background: #0f172a !important; }
    :is(.dark) .fi-sidebar { border-inline-end-color: rgb(148 163 184 / .12) !important; }
    :is(.dark) .fi-sidebar-item-button { color: rgb(var(--gray-300)); }
    :is(.dark) .fi-sidebar-item-button:hover { background-color: rgb(255 255 255 / .05); color: #fff; }
    :is(.dark) .fi-sidebar-item-icon { color: rgb(var(--gray-500)); }
    :is(.dark) .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
    :is(.dark) .fi-sidebar-item-button.fi-active { background-color: rgb(var(--primary-400) / .16); color: rgb(var(--primary-200)); }
    :is(.dark) .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    :is(.dark) .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon { color: rgb(var(--primary-300)); }

    /* ── Panel surface polish (refined / premium) ──────────────────────── */
    .fi-main { --ui-gap: 1.5rem; }
    .fi-section,
    .fi-wi-stats-overview-stat,
    .fi-ta-ctn,
    .fi-fo-tabs {
        box-shadow: 0 1px 2px rgb(2 6 23 / .04), 0 1px 3px rgb(2 6 23 / .04);
        transition: box-shadow .18s ease, border-color .18s ease;
    }
    .fi-section:hover { box-shadow: 0 2px 6px rgb(2 6 23 / .06), 0 2px 10px rgb(2 6 23 / .05); }
    .fi-ta-row { transition: background-color .12s ease; }
    .fi-btn { transition: background-color .15s ease, box-shadow .15s ease, transform .05s ease; }
    .fi-btn:active { transform: translateY(.5px); }

    /* ── Shared UI kit (used by app/resources/views/components/ui/*) ───── */
    /* Compact + crisp: tighter padding, denser type, a touch more border. */
    .ui-card {
        border-radius: .625rem;
        border: 1px solid rgb(var(--gray-950) / .11);
        background: #fff;
        box-shadow: 0 1px 2px rgb(var(--gray-950) / .05);
    }
    .ui-card--pad { padding: 1rem 1.1rem; }
    .ui-card--hover { transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
    .ui-card--hover:hover {
        border-color: rgb(var(--primary-500) / .45);
        box-shadow: 0 4px 12px rgb(var(--gray-950) / .09);
        transform: translateY(-1px);
    }
    .ui-eyebrow {
        font-size: .65rem;
        font-weight: 600;
        letter-spacing: .055em;
        text-transform: uppercase;
        color: rgb(var(--gray-500));
    }
    .ui-stat-value { font-size: 1.4rem; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .ui-progress { height: .4rem; border-radius: 9999px; background: rgb(var(--gray-950) / .1); overflow: hidden; }
    .ui-progress__bar { height: 100%; border-radius: 9999px; background: rgb(var(--primary-500)); transition: width .4s cubic-bezier(.4,0,.2,1); }
    .ui-term {
        text-decoration: none;
        border-bottom: 1px dotted rgb(var(--primary-500) / .6);
        cursor: help;
    }
    .ui-empty { text-align: center; padding: 2rem 1.25rem; }
    .ui-empty__icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem; border-radius: 9999px; margin-bottom: .625rem;
        background: rgb(var(--primary-500) / .1); color: rgb(var(--primary-600));
    }
    .ui-hero {
        position: relative;
        border-radius: .625rem;
        background:
            radial-gradient(120% 160% at 100% 0%, rgb(var(--primary-500) / .1), transparent 55%),
            rgb(var(--primary-500) / .05);
        border: 1px solid rgb(var(--primary-500) / .16);
        padding: 1.05rem 1.25rem 1.05rem 1.4rem;
    }
    .ui-hero::before {
        content: "";
        position: absolute;
        inset-inline-start: 0; top: 0; bottom: 0;
        width: 3px;
        background: rgb(var(--primary-500));
        border-start-start-radius: .625rem;
        border-end-start-radius: .625rem;
    }

    /* Trim the generous default page chrome so screens feel compact. */
    .fi-main { padding-top: 1.25rem !important; padding-bottom: 1.5rem !important; }
    .fi-page > * + * { margin-top: 1rem; }
    .fi-header { margin-bottom: .25rem; }

    /* Responsive grids that don't depend on which Tailwind utilities were
       compiled into the Filament theme build. */
    .ui-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
    @media (min-width: 640px) {
        .ui-grid--2, .ui-grid--3, .ui-grid--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .ui-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .ui-grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    /* ══ Standard admin theme — structured neutral, single accent ═══════ */

    /* Light: a soft cool-grey canvas so the white cards sit forward */
    .fi-body, .fi-main { background: #eef0f4; }
    .fi-topbar { background: #ffffff; border-color: rgb(var(--gray-950) / .09) !important; }
    .fi-section, .fi-ta-ctn, .fi-fo-tabs, .fi-wi-stats-overview-stat {
        border-color: rgb(var(--gray-950) / .1) !important;
        box-shadow: 0 1px 2px rgb(var(--gray-950) / .05), 0 1px 3px rgb(var(--gray-950) / .04);
    }
    .fi-section:hover { box-shadow: 0 4px 12px rgb(var(--gray-950) / .08); }
    .fi-header-heading, .fi-section-header-heading, h1.fi-header-heading { letter-spacing: -.015em; }

    /* ══ Topbar tagline — self-typing one-liner in the empty left stretch ══
       Sits in the TOPBAR_START render hook. Muted so it never competes with
       the page heading; a soft pulsing brand dot + a blinking caret give it
       life. Hidden below md; the Alpine component swaps to a terser message
       set between md and lg. */
    .fi-topbar-tagline {
        display: none;
        align-items: center;
        gap: .5rem;
        min-width: 0;
        max-width: 42vw;
        margin-inline-end: .5rem;
        padding-inline-start: .125rem;
        user-select: none;
        pointer-events: none;
    }
    @media (min-width: 768px)  { .fi-topbar-tagline { display: flex; } }
    @media (min-width: 1280px) { .fi-topbar-tagline { max-width: 34rem; } }

    .fi-topbar-tagline__dot {
        flex: none;
        width: .5rem;
        height: .5rem;
        border-radius: 9999px;
        background: rgb(var(--primary-500));
        box-shadow: 0 0 0 0 rgb(var(--primary-500) / .5);
        animation: fi-topbar-tagline-pulse 2.4s ease-out infinite;
    }
    .fi-topbar-tagline__text {
        font-size: .8125rem;
        font-weight: 500;
        line-height: 1;
        letter-spacing: -.005em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: rgb(var(--gray-500));
    }
    .fi-topbar-tagline__caret {
        flex: none;
        width: 2px;
        height: .9rem;
        margin-inline-start: -.15rem;
        border-radius: 1px;
        background: rgb(var(--primary-500));
        animation: fi-topbar-tagline-blink 1.05s steps(1) infinite;
    }
    /* Hold the caret solid while characters are being typed, blink when idle. */
    .fi-topbar-tagline.is-typing .fi-topbar-tagline__caret { animation: none; opacity: 1; }

    @keyframes fi-topbar-tagline-pulse {
        0%   { box-shadow: 0 0 0 0 rgb(var(--primary-500) / .45); }
        70%  { box-shadow: 0 0 0 .5rem rgb(var(--primary-500) / 0); }
        100% { box-shadow: 0 0 0 0 rgb(var(--primary-500) / 0); }
    }
    @keyframes fi-topbar-tagline-blink {
        0%, 49%   { opacity: 1; }
        50%, 100% { opacity: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fi-topbar-tagline__dot { animation: none; }
        .fi-topbar-tagline__caret { display: none; }
        .fi-topbar-tagline__text { transition: opacity .25s ease; }
    }

    :is(.dark) .fi-topbar-tagline__text  { color: rgb(var(--gray-400)); }
    :is(.dark) .fi-topbar-tagline__dot,
    :is(.dark) .fi-topbar-tagline__caret { background: rgb(var(--primary-400)); }

    /* A discreet Italian tricolore mark at the top of the sidebar. */
    .fi-sidebar { position: relative; }
    .fi-sidebar::before {
        content: "";
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, #008C45 0 33.33%, #eef0ec 33.33% 66.66%, #CD212A 66.66% 100%);
        box-shadow: 0 1px 0 rgb(2 6 23 / .05);
        z-index: 6;
    }

    /* Dark mode: canvas + topbar (the sidebar is already dark, above). */
    :is(.dark) .fi-body,
    :is(.dark) .fi-main { background: #0b1120; }
    :is(.dark) .fi-topbar {
        background: #0f172a;
        border-color: rgb(148 163 184 / .12) !important;
    }
    :is(.dark) .fi-section,
    :is(.dark) .fi-ta-ctn,
    :is(.dark) .fi-fo-tabs,
    :is(.dark) .fi-wi-stats-overview-stat {
        background: #131c31;
        border-color: rgb(148 163 184 / .1) !important;
        box-shadow: none;
    }
    :is(.dark) .fi-section:hover { box-shadow: 0 4px 16px rgb(0 0 0 / .3); }
    :is(.dark) .ui-card { background: #131c31; border-color: rgb(148 163 184 / .1); box-shadow: none; }
    :is(.dark) .ui-card--hover:hover { border-color: rgb(var(--primary-400) / .45); box-shadow: 0 4px 16px rgb(0 0 0 / .35); }
    :is(.dark) .ui-eyebrow { color: rgb(var(--gray-400)); }
    :is(.dark) .ui-progress { background: rgb(255 255 255 / .09); }
    :is(.dark) .ui-hero { background: rgb(var(--primary-400) / .1); border-color: rgb(var(--primary-400) / .22); }
    :is(.dark) .ui-empty__icon { color: rgb(var(--primary-400)); }
</style>
CSS;

        return $base.$custom;
    }

    // Live chat updates (Support Chat page + WhatsApp Inbox). Only emitted
    // when BROADCAST_CONNECTION=reverb and the Reverb client keys are set —
    // otherwise the pages fall back to wire:poll and this stays out of the
    // DOM entirely. Pusher + Echo are pulled from a CDN so no npm build step
    // is required to switch realtime on.
    /**
     * Makes the panel an installable PWA and wires up the web-push bootstrap:
     * the manifest link, a theme-color, the VAPID public key (so the browser
     * can create a subscription), and public/js/push.js which registers the
     * service worker. Push stays dormant until the user opts in from their
     * profile — see resources/views/filament/partials/push-toggle.blade.php.
     */
    protected static function resolvePwaHead(): string
    {
        $manifest = asset('manifest.webmanifest');
        $icon = asset('icons/unihup-icon.svg');
        $pushJs = asset('js/push.js');
        $vapid = e((string) config('webpush.public_key'));

        return <<<HTML
        <link rel="manifest" href="{$manifest}">
        <link rel="apple-touch-icon" href="{$icon}">
        <meta name="theme-color" content="#4f46e5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="UniHup">
        <meta name="webpush-public-key" content="{$vapid}">
        <script src="{$pushJs}" defer></script>
        HTML;
    }

    protected static function resolveEchoScript(): string
    {
        try {
            if (config('broadcasting.default') !== 'reverb') {
                return '';
            }

            $cfg = config('broadcasting.connections.reverb');
        } catch (\Throwable) {
            return '';
        }

        $client = $cfg['client'] ?? $cfg['options'] ?? [];

        if (blank($cfg['key'] ?? null) || blank($client['host'] ?? null)) {
            return '';
        }

        $port = (int) ($client['port'] ?? 443);

        $params = json_encode([
            'broadcaster' => 'reverb',
            'key' => $cfg['key'],
            'wsHost' => $client['host'],
            'wsPort' => $port,
            'wssPort' => $port,
            'forceTLS' => ($client['scheme'] ?? 'https') === 'https',
            'enabledTransports' => ['ws', 'wss'],
        ], JSON_THROW_ON_ERROR);

        return <<<HTML
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
        <script>
            window.Pusher = Pusher;
            window.Echo = new Echo({$params});
        </script>
        HTML;
    }

    // Only render the button once GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET are
    // actually set — otherwise clicking it just throws (Socialite has
    // nothing to redirect to), which is a worse experience than not
    // showing it on a fresh install that hasn't configured OAuth yet.
    protected static function resolveGoogleAuthButton(): View|string
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return '';
        }

        return view('components.google-auth-button');
    }

    protected static function resolveBrandLogoUrl(): ?string
    {
        try {
            $path = Setting::get('app_logo');
        } catch (\Throwable) {
            return null;
        }

        return $path ? Storage::disk('public')->url($path) : null;
    }

    // "favicon" overrides "app_icon" when both are set — matches the field
    // helper text in SystemSettings ("Overrides the app icon for browser tabs").
    protected static function resolveFaviconUrl(): ?string
    {
        try {
            $path = Setting::get('favicon') ?: Setting::get('app_icon');
        } catch (\Throwable) {
            return null;
        }

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
