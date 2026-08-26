<?php

namespace Tests\Unit;

use App\Models\University;
use App\Services\Universities\Enrichers\UniversityWebsiteEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UniversityWebsiteEnricherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_accepts_domains_that_verify_live(): void
    {
        University::create(['name' => 'Università degli studi di Bologna', 'slug' => 'universita-degli-studi-di-bologna', 'city' => 'Bologna']);
        University::create(['name' => 'Università degli studi di Torino', 'slug' => 'universita-degli-studi-di-torino', 'city' => 'Torino']);
        University::create(['name' => 'Already Set', 'slug' => 'already-set', 'city' => 'Rome', 'website_url' => 'https://example.com']);

        Http::fake([
            'https://www.unibo.it' => Http::response('<html>Alma Mater Studiorum Università di Bologna</html>', 200),
            'https://www.unito.it' => Http::response('Not found', 404),
            'https://unito.it' => Http::response('This is a completely unrelated parked domain page.', 200),
        ]);

        $result = (new UniversityWebsiteEnricher)->enrich();

        $this->assertSame(1, $result->updated); // only Bologna verifies
        $this->assertSame(1, $result->skipped); // Torino's candidates both fail verification

        $this->assertSame('https://www.unibo.it', University::where('slug', 'universita-degli-studi-di-bologna')->value('website_url'));
        $this->assertNull(University::where('slug', 'universita-degli-studi-di-torino')->value('website_url'));
        $this->assertSame('https://example.com', University::where('slug', 'already-set')->value('website_url'));
    }
}
