<?php

namespace App\Services\Universities\Enrichers;

use App\Contracts\DataEnricher;
use App\Contracts\EnrichmentResult;
use App\Models\University;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fetches each university's own site icon (apple-touch-icon, or failing
 * that its favicon) and stores it locally, rather than embedding a
 * third-party badge service (e.g. Google's favicon proxy) into every page
 * render — this way the image is self-hosted, verified once, and doesn't
 * depend on an external service staying up.
 *
 * Only runs for universities that already have a verified website_url
 * (see UniversityWebsiteEnricher) and no logo yet, and only ever stores
 * bytes that actually look like an image (checked via Content-Type and, as
 * a fallback, magic-byte sniffing) — a 404 page or redirect-to-login HTML
 * response is silently skipped, not saved as someone's "logo".
 */
class UniversityLogoEnricher implements DataEnricher
{
    private const USER_AGENT = 'unihup-import/1.0';

    public function enrich(): EnrichmentResult
    {
        $updated = 0;
        $skipped = 0;

        University::whereNull('logo')
            ->whereNotNull('website_url')
            ->get(['id', 'slug', 'website_url'])
            ->each(function (University $university) use (&$updated, &$skipped) {
                if ($this->fetchAndStore($university)) {
                    $updated++;
                } else {
                    $skipped++;
                }
            });

        return new EnrichmentResult(
            updated: $updated,
            skipped: $skipped,
            summary: "Fetched a logo for {$updated} universities from their own site icon (skipped {$skipped} with no usable icon).",
        );
    }

    private function fetchAndStore(University $university): bool
    {
        $iconUrl = $this->discoverIconUrl($university->website_url);

        if (! $iconUrl) {
            return false;
        }

        try {
            $response = Http::timeout(10)->withUserAgent(self::USER_AGENT)->get($iconUrl);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $contentType = $response->header('Content-Type');
        $body = $response->body();

        if (! $this->looksLikeImage($contentType, $body)) {
            return false;
        }

        $extension = $this->extensionFromContentType($contentType) ?? 'png';
        $path = "logos/{$university->slug}.{$extension}";

        Storage::disk('public')->put($path, $body);
        $university->update(['logo' => $path]);

        return true;
    }

    private function discoverIconUrl(string $siteUrl): ?string
    {
        try {
            $html = Http::timeout(10)->withUserAgent(self::USER_AGENT)->get($siteUrl)->body();
        } catch (\Throwable) {
            return null;
        }

        $fallback = null;

        if (preg_match_all('#<link\b[^>]*>#i', $html, $tags)) {
            foreach ($tags[0] as $tag) {
                if (! preg_match('/rel=["\']([^"\']+)["\']/i', $tag, $relMatch)) {
                    continue;
                }

                $rel = mb_strtolower($relMatch[1]);
                if (! Str::contains($rel, 'icon')) {
                    continue;
                }

                if (! preg_match('/href=["\']([^"\']+)["\']/i', $tag, $hrefMatch)) {
                    continue;
                }

                $href = $this->resolveUrl($siteUrl, html_entity_decode($hrefMatch[1]));

                // Prefer a touch icon — usually a proper square logo, not a tiny 16x16 favicon.
                if (Str::contains($rel, 'apple-touch-icon')) {
                    return $href;
                }

                $fallback ??= $href;
            }
        }

        return $fallback ?? $this->resolveUrl($siteUrl, '/favicon.ico');
    }

    private function resolveUrl(string $base, string $href): string
    {
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        if (Str::startsWith($href, '//')) {
            return 'https:'.$href;
        }

        $parsed = parse_url($base);
        $root = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');

        return Str::startsWith($href, '/')
            ? $root.$href
            : rtrim($base, '/').'/'.$href;
    }

    private function looksLikeImage(?string $contentType, string $body): bool
    {
        if ($contentType && Str::startsWith($contentType, 'image/')) {
            return true;
        }

        return (bool) preg_match('/^(\x89PNG|\x00\x00\x01\x00|\xFF\xD8|GIF8|<svg|<\?xml)/', $body);
    }

    private function extensionFromContentType(?string $contentType): ?string
    {
        if (! $contentType) {
            return null;
        }

        return match (true) {
            str_contains($contentType, 'svg') => 'svg',
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'icon') => 'ico',
            default => null,
        };
    }
}
