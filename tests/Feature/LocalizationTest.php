<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function pass(string $uri): Request
    {
        $request = Request::create($uri);
        $request->setLaravelSession($this->app['session']->driver());

        (new SetLocale)->handle($request, fn ($r) => new Response);

        return $request;
    }

    public function test_lang_query_param_switches_and_sticks_for_the_session(): void
    {
        $request = $this->pass('/anything?lang=it');

        $this->assertSame('it', $request->session()->get('locale'));
        $this->assertSame('it', app()->getLocale());
    }

    public function test_unsupported_locales_are_ignored(): void
    {
        $request = $this->pass('/anything?lang=fr');

        $this->assertNull($request->session()->get('locale'));
        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_the_ui_namespace_is_mirrored_across_locales(): void
    {
        $en = array_keys(Arr::dot(require lang_path('en/ui.php')));
        $it = array_keys(Arr::dot(require lang_path('it/ui.php')));

        $this->assertSame($en, $it, 'lang/it/ui.php must carry the same keys as lang/en/ui.php');
        $this->assertContains('en', SetLocale::SUPPORTED);
        $this->assertContains('it', SetLocale::SUPPORTED);
    }
}
