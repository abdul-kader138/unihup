<?php

namespace App\Console\Commands;

use App\Models\CityGuide;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\LinkCheckResult;
use App\Models\RegionalScholarship;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pings every external URL referenced in the catalog (program admission
 * pages, scholarship sites, deadline sources, city-guide links) and records
 * the result in link_check_results. The admin data-freshness widget surfaces
 * the failures. Scheduled weekly.
 */
class CheckLinks extends Command
{
    protected $signature = 'unihup:check-links {--limit=0 : Only check this many (0 = all)} {--stale-hours=144 : Re-check a URL only if last checked longer ago than this}';

    protected $description = 'Check that external catalog links still resolve';

    public function handle(): int
    {
        $targets = $this->collectUrls();
        $limit = (int) $this->option('limit');
        $staleBefore = now()->subHours((int) $this->option('stale-hours'));

        $checked = 0;
        $broken = 0;

        foreach ($targets as $url => $source) {
            if ($limit > 0 && $checked >= $limit) {
                break;
            }

            $hash = sha1($url);
            $existing = LinkCheckResult::where('url_hash', $hash)->first();

            if ($existing && $existing->checked_at?->gt($staleBefore)) {
                continue; // still fresh
            }

            [$code, $ok, $error] = $this->probe($url);
            $checked++;
            $broken += $ok ? 0 : 1;

            LinkCheckResult::updateOrCreate(
                ['url_hash' => $hash],
                [
                    'url' => mb_substr($url, 0, 1024),
                    'status_code' => $code,
                    'ok' => $ok,
                    'error' => $error,
                    'source' => $source,
                    'checked_at' => now(),
                ],
            );
        }

        // Drop rows for URLs that no longer appear anywhere.
        $currentHashes = collect(array_keys($targets))->map(fn (string $u) => sha1($u))->all();
        LinkCheckResult::whereNotIn('url_hash', $currentHashes)->delete();

        $this->info("Checked {$checked} link(s); {$broken} broken.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> url => human source label
     */
    private function collectUrls(): array
    {
        $out = [];

        $add = function (?string $url, string $source) use (&$out): void {
            $url = trim((string) $url);
            if ($url !== '' && str_starts_with($url, 'http') && ! isset($out[$url])) {
                $out[$url] = $source;
            }
        };

        DegreeProgram::query()->select('id', 'official_admission_url', 'source_url')->cursor()
            ->each(function (DegreeProgram $p) use ($add) {
                $add($p->official_admission_url, "DegreeProgram #{$p->id} official_admission_url");
                $add($p->source_url, "DegreeProgram #{$p->id} source_url");
            });

        RegionalScholarship::query()->select('id', 'website_url', 'source_url')->cursor()
            ->each(function (RegionalScholarship $s) use ($add) {
                $add($s->website_url, "RegionalScholarship #{$s->id} website_url");
                $add($s->source_url, "RegionalScholarship #{$s->id} source_url");
            });

        Deadline::query()->select('id', 'url', 'source_url')->cursor()
            ->each(function (Deadline $d) use ($add) {
                $add($d->url, "Deadline #{$d->id} url");
                $add($d->source_url, "Deadline #{$d->id} source_url");
            });

        CityGuide::query()->select('id', 'useful_links')->cursor()
            ->each(function (CityGuide $g) use ($add) {
                foreach ($g->useful_links ?? [] as $link) {
                    $add($link['url'] ?? null, "CityGuide #{$g->id} useful_links");
                }
            });

        return $out;
    }

    /**
     * @return array{0: int|null, 1: bool, 2: string|null}
     */
    private function probe(string $url): array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => 'UniHup-LinkChecker/1.0'])
                ->timeout(12)
                ->connectTimeout(6)
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            $code = $res->status();

            return [$code, $code < 400, $code >= 400 ? "HTTP {$code}" : null];
        } catch (\Throwable $e) {
            return [null, false, class_basename($e).': '.mb_substr($e->getMessage(), 0, 120)];
        }
    }
}
