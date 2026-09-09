<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Plain-language definitions of the Italian higher-education / immigration
 * terms international applicants keep hitting. Uniform national terminology
 * (like App\Support\AdmissionTestCopy) — curated text, not per-university
 * data. Surfaced as a glossary on the Help Center and inline via the
 * <x-term> Blade component.
 */
final class Glossary
{
    /**
     * @var array<string, array{term: string, definition: string}>
     */
    public const TERMS = [
        'laurea-triennale' => [
            'term' => 'Laurea triennale',
            'definition' => 'A 3-year first-cycle degree, equivalent to a Bachelor. The entry point for most undergraduate applicants.',
        ],
        'laurea-magistrale' => [
            'term' => 'Laurea magistrale',
            'definition' => 'A 2-year second-cycle degree, equivalent to a Master. Requires a completed Bachelor in a related field.',
        ],
        'libero-accesso' => [
            'term' => 'Libero accesso (open access)',
            'definition' => 'Admission is open to anyone who meets the entry requirements — sometimes with a non-blocking placement test.',
        ],
        'numero-programmato' => [
            'term' => 'Numero programmato (restricted)',
            'definition' => 'A fixed number of seats, allocated by a competitive entrance test (TOLC or IMAT). You must sit the test and rank within the seats.',
        ],
        'bando' => [
            'term' => 'Bando',
            'definition' => "A program's official admission notice — the binding source for requirements, documents, deadlines and fees. Always read the bando.",
        ],
        'ofa' => [
            'term' => 'OFA',
            'definition' => 'Obbligo Formativo Aggiuntivo — a remedial requirement added to your first year if your admission-test score was below a threshold. Not usually a bar to enrolling.',
        ],
        'immatricolazione' => [
            'term' => 'Immatricolazione',
            'definition' => 'Formal enrolment / matriculation once admitted: pay the first tuition instalment, submit recognised documents, get your student number.',
        ],
        'matricola' => [
            'term' => 'Matricola',
            'definition' => 'Your university student ID number (and, colloquially, a first-year student).',
        ],
        'tolc' => [
            'term' => 'TOLC',
            'definition' => 'Test OnLine CISIA — the standardized, subject-specific admission test used by most Italian public universities for restricted programs.',
        ],
        'imat' => [
            'term' => 'IMAT',
            'definition' => 'International Medical Admissions Test — the entrance exam for English-taught Medicine, Dentistry and Veterinary Medicine, run once a year via Universitaly.',
        ],
        'semestre-filtro' => [
            'term' => 'Semestre filtro',
            'definition' => 'The open-access "filter semester" that replaced the single entrance exam for Italian-taught Medicine/Dentistry/Vet from 2024/25: enrol, sit national exams, then rank for a place.',
        ],
        'preiscrizione' => [
            'term' => 'Preiscrizione (pre-enrolment)',
            'definition' => 'Pre-enrolment on the Universitaly portal. Non-EU applicants must complete it before a study visa can be issued.',
        ],
        'universitaly' => [
            'term' => 'Universitaly',
            'definition' => "The Ministry of University's official portal — used for pre-enrolment, IMAT registration, and sometimes issuing your codice fiscale.",
        ],
        'dov' => [
            'term' => 'Dichiarazione di Valore (DoV)',
            'definition' => 'A statement from the Italian embassy/consulate in the country where you studied, explaining your qualification in terms Italian universities can evaluate.',
        ],
        'cimea' => [
            'term' => 'CIMEA statement',
            'definition' => "Italy's ENIC-NARIC centre issues a Statement of Comparability (qualification level) and a Statement of Verification (authenticity) online — accepted by many universities in place of a DoV.",
        ],
        'permesso-di-soggiorno' => [
            'term' => 'Permesso di soggiorno',
            'definition' => 'The residence permit. Non-EU students must apply for it at a Poste Italiane Sportello Amico within 8 working days of arriving in Italy.',
        ],
        'codice-fiscale' => [
            'term' => 'Codice fiscale',
            'definition' => 'The Italian tax code — needed to sign a lease, open a bank account and register with the health service.',
        ],
        'isee' => [
            'term' => 'ISEE',
            'definition' => 'Indicatore della Situazione Economica Equivalente — a household income + asset indicator that sets your tuition bracket and scholarship eligibility.',
        ],
        'isee-parificato' => [
            'term' => 'ISEE Parificato',
            'definition' => 'The foreign-income equivalent of the ISEE, calculated by a CAF affiliated with your university. File it by the deadline (often ~30 September) or you pay the top fee bracket.',
        ],
        'dsu' => [
            'term' => 'DSU (diritto allo studio)',
            'definition' => 'Right-to-study: regional, income-tested scholarships, subsidised housing and meal plans, run by a regional body (e.g. DSU Lombardia, ER.GO, EDISU).',
        ],
        'tassa-regionale' => [
            'term' => 'Tassa regionale',
            'definition' => 'A regional student tax (roughly €140–160/year) owed even when your tuition is in the no-tax band.',
        ],
        'visto-d' => [
            'term' => 'Type D visa',
            'definition' => 'The national long-stay student visa issued by the Italian consulate, required for study longer than 90 days.',
        ],
    ];

    /**
     * @return array{term: string, definition: string}|null
     */
    public static function get(string $key): ?array
    {
        return self::TERMS[$key] ?? null;
    }

    public static function definition(string $key): ?string
    {
        return self::TERMS[$key]['definition'] ?? null;
    }

    /**
     * All entries, alphabetised by term.
     *
     * @return array<int, array{key: string, term: string, definition: string}>
     */
    public static function all(): array
    {
        return collect(self::TERMS)
            ->map(fn (array $v, string $k) => ['key' => $k] + $v)
            ->sortBy(fn (array $v) => Str::lower($v['term']))
            ->values()
            ->all();
    }
}
