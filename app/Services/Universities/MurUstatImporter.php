<?php

namespace App\Services\Universities;

use App\Contracts\ImportResult;
use App\Contracts\UniversityDataImporter;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Live data source backed by MUR/USTAT's official open dataset
 * (dati-ustat.mur.gov.it, IODL 2.0 license) — the "Offerta formativa"
 * course-catalogue CSVs. Unlike universitaly.it (a client-side SPA with no
 * public API — see UniversitalyImporter), this data ships as flat CSV
 * files on a stable CKAN portal, so it can be fetched with a plain HTTP
 * GET and needs no headless browser.
 *
 * Two resources are combined, joined on the institution's short
 * "NomeOperativo" (e.g. "Bologna"), which is distinct from the
 * long official name used for University.name/slug:
 * - "Atenei" (~110 rows): one row per institution (name, city, region, status).
 * - "Offerta formativa 2010-2024" (~76k rows): one row per degree
 *   programme per academic year (institution, subject taxonomy, degree
 *   class code, course name, admission type).
 *
 * The files are ISO-8859-1 (Windows-1252) encoded, not UTF-8 — converted
 * on read. Only the most recent academic year present in the file is
 * imported (prior years are historical, not current course offerings).
 *
 * Degree-class code prefixes map deterministically for the current
 * dataset: codes starting "L-" or "SNT" are 3-year bachelor's; codes
 * starting "LM-" are 2-year master's; LMG/01 (Law) and LMR/02 (Dentistry)
 * are single-cycle programmes,
 * modelled as 'bachelor' with a longer duration_years — the same
 * convention DegreeProgramSeeder already uses for combined degrees like
 * Medicine and Surgery. Any unrecognised prefix is skipped rather than
 * guessed at.
 */
class MurUstatImporter implements UniversityDataImporter
{
    private const ATENEI_URL = 'https://dati-ustat.mur.gov.it/dataset/bed0c71e-9f86-4a0f-a266-963b6f7bbbd2/resource/820aefe6-0662-4656-84ec-d8859a2a3b7e/download/atenei.csv';

    private const CORSI_URL = 'https://dati-ustat.mur.gov.it/dataset/bed0c71e-9f86-4a0f-a266-963b6f7bbbd2/resource/c0e63906-7190-4568-892b-0cf399f56071/download/corsidilaurea_2010-2024.csv';

    private const SOURCE_URL = 'https://dati-ustat.mur.gov.it/dataset/bed0c71e-9f86-4a0f-a266-963b6f7bbbd2';

    /**
     * [degree_level, duration_years], keyed by degree-class code prefix.
     * See class doc comment for why LMG/01 and LMR/02 are singled out.
     */
    private const DEGREE_CLASS_MAP = [
        'L' => ['bachelor', 3],
        'SNT' => ['bachelor', 3],
        'LMG' => ['bachelor', 5],
        'LMR' => ['bachelor', 6],
        'LM' => ['master', 2],
    ];

    public function __construct(private readonly ?int $year = null) {}

    public function import(): ImportResult
    {
        $institutions = $this->fetchInstitutions();
        $rows = $this->fetchCourseRows();

        $year = $this->year ?? max(array_column($rows, 'year'));
        $rows = array_values(array_filter($rows, fn (array $row) => $row['year'] === $year));

        // Drop rows for institutions we don't recognize (closed/merged, or a
        // join-key mismatch) up front so every downstream count is accurate.
        $rows = array_values(array_filter($rows, fn (array $row) => isset($institutions[$row['university_key']])));

        $universityIdsByKey = $this->upsertUniversities($institutions, $rows);
        $subjectIdsByName = $this->upsertSubjects($rows);
        $programCount = $this->upsertDegreePrograms($rows, $universityIdsByKey, $subjectIdsByName);

        return new ImportResult(
            universities: count($universityIdsByKey),
            subjects: count($subjectIdsByName),
            degreePrograms: $programCount,
            summary: "Imported MUR/USTAT open data for academic year {$year} (".self::SOURCE_URL.').',
        );
    }

    /** @return array<string, array{name: string, city: ?string, region: ?string}> keyed by normalized NomeOperativo */
    private function fetchInstitutions(): array
    {
        $header = null;
        $institutions = [];

        foreach ($this->downloadCsvRows(self::ATENEI_URL) as $fields) {
            if ($header === null) {
                $header = $fields;

                continue;
            }

            $col = array_flip($header);
            if (($fields[$col['Status']] ?? null) !== 'A') {
                continue; // skip merged/closed institutions
            }

            $key = $this->normalizeKey($fields[$col['NomeOperativo']] ?? '');
            if ($key === '') {
                continue;
            }

            $institutions[$key] = [
                'name' => trim($fields[$col['NomeEsteso']] ?? $fields[$col['NomeOperativo']]),
                'city' => $this->titleCase($fields[$col['CITTA']] ?? null),
                'region' => $this->titleCase($fields[$col['REGIONE']] ?? null),
            ];
        }

        return $institutions;
    }

    /** @return list<array{year: int, university_key: string, subject: string, class_code: string, course_name: string, admission_type: string}> */
    private function fetchCourseRows(): array
    {
        $header = null;
        $rows = [];

        foreach ($this->downloadCsvRows(self::CORSI_URL) as $fields) {
            if ($header === null) {
                $header = $fields;

                continue;
            }

            $col = array_flip($header);
            if (! isset($fields[$col['NUMERO']])) {
                continue;
            }

            $rows[] = [
                'year' => (int) $fields[$col['ANNO_VALIDITA']],
                'university_key' => $this->normalizeKey($fields[$col['NomeOperativo']] ?? ''),
                'subject' => trim($fields[$col['DES']] ?? ''),
                'class_code' => trim($fields[$col['NUMERO']] ?? ''),
                'course_name' => trim($fields[$col['NOME_CORSO']] ?? ''),
                'admission_type' => trim($fields[$col['ACCESSO']] ?? '') === 'accesso libero' ? 'open' : 'restricted',
            ];
        }

        return $rows;
    }

    /** @return array<string, int> normalized university_key => university id */
    private function upsertUniversities(array $institutions, array $rows): array
    {
        $neededKeys = array_unique(array_column($rows, 'university_key'));

        $records = [];
        foreach ($neededKeys as $key) {
            $info = $institutions[$key] ?? null;
            if (! $info) {
                continue;
            }

            $records[$key] = [
                'name' => $info['name'],
                'slug' => Str::slug($info['name']),
                'city' => $info['city'] ?? 'Italy',
                'region' => $info['region'],
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if ($records === []) {
            return [];
        }

        University::upsert(array_values($records), uniqueBy: ['slug'], update: ['name', 'city', 'region', 'updated_at']);

        $idsBySlug = University::whereIn('slug', array_column($records, 'slug'))->pluck('id', 'slug');

        $idsByKey = [];
        foreach ($records as $key => $record) {
            $idsByKey[$key] = $idsBySlug[$record['slug']] ?? null;
        }

        return array_filter($idsByKey);
    }

    /** @return array<string, int> subject name => id */
    private function upsertSubjects(array $rows): array
    {
        $names = array_unique(array_filter(array_column($rows, 'subject')));

        $records = array_map(fn (string $name) => [
            'name' => $name,
            'slug' => Str::slug($name),
            'updated_at' => now(),
            'created_at' => now(),
        ], array_values($names));

        if ($records === []) {
            return [];
        }

        Subject::upsert($records, uniqueBy: ['slug'], update: ['name', 'updated_at']);

        return Subject::whereIn('slug', array_column($records, 'slug'))
            ->get(['id', 'name'])
            ->pluck('id', 'name')
            ->all();
    }

    /**
     * @param  array<string, int>  $universityIdsByKey
     * @param  array<string, int>  $subjectIdsByName
     */
    private function upsertDegreePrograms(array $rows, array $universityIdsByKey, array $subjectIdsByName): int
    {
        // The same course can appear more than once per university (e.g. offered at
        // several campus locations) — the source rows aren't unique on our natural
        // key, so dedupe before upserting rather than reporting inflated counts.
        $records = [];

        foreach ($rows as $row) {
            $mapping = $this->mapDegreeClass($row['class_code']);
            $universityId = $universityIdsByKey[$row['university_key']] ?? null;
            $subjectId = $subjectIdsByName[$row['subject']] ?? null;

            if (! $mapping || $row['course_name'] === '' || ! $universityId || ! $subjectId) {
                continue;
            }

            [$degreeLevel, $durationYears] = $mapping;

            $key = implode('|', [$universityId, $subjectId, $degreeLevel, $row['course_name']]);

            $records[$key] = [
                'university_id' => $universityId,
                'subject_id' => $subjectId,
                'degree_level' => $degreeLevel,
                'name' => $row['course_name'],
                'language' => 'Italian',
                'duration_years' => $durationYears,
                'admission_type' => $row['admission_type'],
                'source_url' => self::SOURCE_URL,
                'last_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        foreach (array_chunk(array_values($records), 500) as $chunk) {
            DegreeProgram::upsert(
                $chunk,
                uniqueBy: ['university_id', 'subject_id', 'degree_level', 'name'],
                update: ['language', 'duration_years', 'admission_type', 'source_url', 'last_verified_at', 'updated_at'],
            );
        }

        return count($records);
    }

    private function mapDegreeClass(string $classCode): ?array
    {
        $prefix = strtoupper(explode('/', $classCode)[0] ?? '');
        $prefix = preg_replace('/[^A-Z]/', '', $prefix); // 'L-1' -> 'L', 'LM-41' -> 'LM'

        return self::DEGREE_CLASS_MAP[$prefix] ?? null;
    }

    private function normalizeKey(string $value): string
    {
        return Str::slug(trim($value));
    }

    private function titleCase(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::title(mb_strtolower($value));
    }

    /**
     * @return iterable<list<string>> raw CSV rows including the header row
     *
     * The two source files disagree on encoding: atenei.csv ships as UTF-8
     * with a BOM, while corsidilaurea's is Windows-1252 (confirmed by
     * inspecting raw bytes — 0xE0 for "à" rather than UTF-8's 0xC3 0xA0).
     * Converting a file that's already valid UTF-8 would double-encode it
     * into mojibake, so only convert when the raw bytes aren't valid UTF-8.
     */
    private function downloadCsvRows(string $url): iterable
    {
        $response = Http::timeout(120)->retry(3, 500)->get($url)->throw();
        $body = $response->body();

        if (! mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'Windows-1252');
        }

        $body = preg_replace('/^\x{FEFF}/u', '', $body);

        foreach (preg_split('/\r\n|\n|\r/', trim($body)) as $line) {
            yield str_getcsv($line, ';');
        }
    }
}
