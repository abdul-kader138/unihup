<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
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

    public function test_compare_page_renders_translated_copy_when_locale_is_italian(): void
    {
        $this->seed(ShieldSeeder::class);
        $user = User::factory()->create(['study_profile_completed_at' => now()]);
        $user->assignRole('panel_user');

        $subject = Subject::create(['name' => 'CS', 'slug' => 'cs']);
        $university = University::create(['name' => 'Uni', 'slug' => 'uni', 'city' => 'Rome']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ]);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        $this->actingAs($user)->get('/compare')->assertSee('Choose which to compare');

        $this->actingAs($user)
            ->withSession(['locale' => 'it'])
            ->get('/compare')
            ->assertSee('Scegli cosa confrontare')
            ->assertDontSee('Choose which to compare');
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
