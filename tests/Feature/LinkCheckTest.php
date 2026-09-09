<?php

namespace Tests\Feature;

use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\LinkCheckResult;
use App\Models\Subject;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class LinkCheckTest extends TestCase
{
    use RefreshDatabase;

    private function program(array $urls): DegreeProgram
    {
        $subject = Subject::firstOrCreate(['slug' => 'x'], ['name' => 'X']);
        $slug = 'u-'.Str::random(6);
        $u = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'C']);

        return DegreeProgram::create(array_merge([
            'university_id' => $u->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'P', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ], $urls));
    }

    public function test_it_records_ok_and_broken_links(): void
    {
        Http::fake([
            'good.example/*' => Http::response('ok', 200),
            'dead.example/*' => Http::response('gone', 404),
        ]);

        $this->program(['official_admission_url' => 'https://good.example/a', 'source_url' => 'https://dead.example/b']);

        $this->artisan('unihup:check-links')->assertSuccessful();

        $this->assertDatabaseHas('link_check_results', ['url' => 'https://good.example/a', 'ok' => true, 'status_code' => 200]);
        $this->assertDatabaseHas('link_check_results', ['url' => 'https://dead.example/b', 'ok' => false, 'status_code' => 404]);
        $this->assertSame(1, LinkCheckResult::broken()->count());
    }

    public function test_a_connection_failure_is_recorded_as_broken(): void
    {
        Http::fake(fn () => throw new ConnectionException('nope'));

        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'D', 'category' => 'other',
            'due_at' => now()->addDay(), 'url' => 'https://unreachable.example/x', 'is_active' => true,
        ]);

        $this->artisan('unihup:check-links')->assertSuccessful();

        $row = LinkCheckResult::first();
        $this->assertFalse($row->ok);
        $this->assertNull($row->status_code);
        $this->assertNotNull($row->error);
    }

    public function test_fresh_results_are_not_re_checked(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $this->program(['official_admission_url' => 'https://good.example/a']);

        $this->artisan('unihup:check-links')->assertSuccessful();
        $first = LinkCheckResult::first()->checked_at;

        $this->travel(1)->hours();
        $this->artisan('unihup:check-links')->assertSuccessful();

        $this->assertEquals($first->timestamp, LinkCheckResult::first()->checked_at->timestamp);
    }

    public function test_results_for_urls_no_longer_referenced_are_pruned(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        LinkCheckResult::create([
            'url' => 'https://orphan.example/gone', 'url_hash' => sha1('https://orphan.example/gone'),
            'ok' => true, 'status_code' => 200, 'checked_at' => now()->subYear(),
        ]);

        $this->artisan('unihup:check-links')->assertSuccessful();

        $this->assertDatabaseMissing('link_check_results', ['url' => 'https://orphan.example/gone']);
    }
}
