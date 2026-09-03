<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The panel is the site's only login now — see App\Filament\Auth\LoginResponse.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Meta's WhatsApp webhook is a server-to-server POST with no session;
        // it's authenticated by X-Hub-Signature-256, not a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
