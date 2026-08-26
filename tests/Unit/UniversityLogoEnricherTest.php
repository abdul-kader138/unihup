<?php

namespace Tests\Unit;

use App\Models\University;
use App\Services\Universities\Enrichers\UniversityLogoEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UniversityLogoEnricherTest extends TestCase
{
    use RefreshDatabase;

    // Smallest valid PNG: a 1x1 transparent pixel.
    private const PNG_BYTES = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";

    public function test_it_finds_the_apple_touch_icon_and_stores_it(): void
    {
        Storage::fake('public');

        $html = '<html><head><link rel="apple-touch-icon" href="/assets/touch-icon.png"><link rel="icon" href="/favicon.ico"></head></html>';

        Http::fake([
            'https://example-uni.test/' => Http::response($html, 200),
            'https://example-uni.test/assets/touch-icon.png' => Http::response(self::PNG_BYTES, 200, ['Content-Type' => 'image/png']),
        ]);

        $university = University::create([
            'name' => 'Example University', 'slug' => 'example-university', 'city' => 'Rome',
            'website_url' => 'https://example-uni.test/',
        ]);

        $result = (new UniversityLogoEnricher)->enrich();

        $this->assertSame(1, $result->updated);
        $university->refresh();
        $this->assertSame('logos/example-university.png', $university->logo);
        Storage::disk('public')->assertExists('logos/example-university.png');
    }

    public function test_it_skips_universities_with_no_usable_icon(): void
    {
        Http::fake([
            'https://no-icon-uni.test/' => Http::response('<html><head></head></html>', 200),
            'https://no-icon-uni.test/favicon.ico' => Http::response('<html>404 not found</html>', 404),
        ]);

        $university = University::create([
            'name' => 'No Icon University', 'slug' => 'no-icon-university', 'city' => 'Milan',
            'website_url' => 'https://no-icon-uni.test/',
        ]);

        $result = (new UniversityLogoEnricher)->enrich();

        $this->assertSame(0, $result->updated);
        $this->assertSame(1, $result->skipped);
        $this->assertNull($university->fresh()->logo);
    }

    public function test_it_does_not_save_an_html_error_page_as_a_logo(): void
    {
        // favicon.ico returns 200 but with an HTML body (soft-404) — must not be trusted just because of the status code.
        Http::fake([
            'https://soft404-uni.test/' => Http::response('<html><head></head></html>', 200),
            'https://soft404-uni.test/favicon.ico' => Http::response('<html><body>Not Found</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $university = University::create([
            'name' => 'Soft404 University', 'slug' => 'soft404-university', 'city' => 'Turin',
            'website_url' => 'https://soft404-uni.test/',
        ]);

        (new UniversityLogoEnricher)->enrich();

        $this->assertNull($university->fresh()->logo);
    }

    public function test_it_skips_universities_without_a_website_url(): void
    {
        University::create(['name' => 'No Website University', 'slug' => 'no-website-university', 'city' => 'Naples']);

        $result = (new UniversityLogoEnricher)->enrich();

        $this->assertSame(0, $result->updated);
        $this->assertSame(0, $result->skipped);
    }
}
