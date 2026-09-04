<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CanonicalAcademicNames
{
    /** @var array<string, string> */
    private const SUBJECTS = [
        'informatica' => 'Computer Science',
        'computer science' => 'Computer Science',
        'architettura' => 'Architecture',
        'architecture' => 'Architecture',
        'medicina e chirurgia' => 'Medicine and Surgery',
        'medicine and surgery' => 'Medicine and Surgery',
        'economia' => 'Economics',
        'economics' => 'Economics',
        'giurisprudenza' => 'Law',
        'law' => 'Law',
        'ingegneria meccanica' => 'Mechanical Engineering',
        'mechanical engineering' => 'Mechanical Engineering',
        'ingegneria elettrica' => 'Electrical Engineering',
        'electrical engineering' => 'Electrical Engineering',
        'ingegneria civile' => 'Civil Engineering',
        'civil engineering' => 'Civil Engineering',
        'fisica' => 'Physics',
        'physics' => 'Physics',
        'matematica' => 'Mathematics',
        'mathematics' => 'Mathematics',
        'biologia' => 'Biology',
        'biology' => 'Biology',
        'relazioni internazionali' => 'International Relations',
        'international relations' => 'International Relations',
        'design' => 'Design',
        'psicologia' => 'Psychology',
        'psychology' => 'Psychology',
    ];

    /** @var array<string, string> */
    private const UNIVERSITIES = [
        'universita degli studi di bologna' => 'University of Bologna',
        'university of bologna' => 'University of Bologna',
        'universita degli studi di torino' => 'University of Turin',
        'university of turin' => 'University of Turin',
        'universita degli studi di milano' => 'University of Milan',
        'university of milan' => 'University of Milan',
        'universita degli studi di padova' => 'University of Padua',
        'university of padua' => 'University of Padua',
        'universita degli studi di firenze' => 'University of Florence',
        'university of florence' => 'University of Florence',
        'universita degli studi di pisa' => 'University of Pisa',
        'university of pisa' => 'University of Pisa',
        'universita degli studi di napoli federico ii' => 'University of Naples Federico II',
        'university of naples federico ii' => 'University of Naples Federico II',
        'politecnico di milano' => 'Politecnico di Milano',
        'politecnico di torino' => 'Politecnico di Torino',
        'universita commerciale luigi bocconi' => 'Bocconi University',
        'bocconi university' => 'Bocconi University',
        'universita degli studi di trento' => 'University of Trento',
        'university of trento' => 'University of Trento',
        'universita ca foscari venezia' => "Ca' Foscari University of Venice",
        'ca foscari university of venice' => "Ca' Foscari University of Venice",
        'universita degli studi di genova' => 'University of Genoa',
        'university of genoa' => 'University of Genoa',
        'universita degli studi di bari aldo moro' => 'University of Bari Aldo Moro',
        'university of bari aldo moro' => 'University of Bari Aldo Moro',
        'sapienza universita di roma' => 'Sapienza University of Rome',
        'sapienza university of rome' => 'Sapienza University of Rome',
    ];

    public static function subject(string $name): string
    {
        return self::SUBJECTS[self::key($name)] ?? trim($name);
    }

    public static function university(string $name): string
    {
        return self::UNIVERSITIES[self::key($name)] ?? trim($name);
    }

    private static function key(string $name): string
    {
        return (string) Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim();
    }
}
