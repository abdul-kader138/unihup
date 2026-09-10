<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale, in order: an explicit `?lang=` (persisted to
 * the session), the session, then the app default. Restricted to the
 * locales the app actually ships strings for — see the lang directory. This
 * is the hook translators build on; most user-facing copy still needs
 * extracting into per-locale ui.php files before switching shows visibly.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'it'];

    public function handle(Request $request, Closure $next): Response
    {
        if (($choice = $request->query('lang')) && in_array($choice, self::SUPPORTED, true)) {
            $request->session()->put('locale', $choice);
        }

        $locale = $request->session()->get('locale', config('app.locale'));

        if (in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
