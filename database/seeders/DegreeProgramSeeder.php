<?php

namespace Database\Seeders;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * A curated, real (not fabricated) starter set of Italian degree programs.
 * Admission/tuition/application-window text is deliberately general —
 * exact deadlines and fees change yearly and vary by applicant nationality
 * and income, so every row also carries an official_admission_url and
 * source_url a student should check before relying on anything here. See
 * App\Services\Universities\SeedDataImporter, which runs this seeder as
 * the default (and currently only working) university data source.
 */
class DegreeProgramSeeder extends Seeder
{
    private const OPEN_NOTE = "Open access (libero accesso): apply directly through the university's online enrollment portal. No entrance exam is required to be admitted, though some programs ask you to sit a non-selective self-assessment test (TOLC) first.";

    private const RESTRICTED_NOTE = 'Restricted access (numero programmato): admission is capped and decided by a national or university-run entrance exam. Seats go by rank order of exam score, for EU and non-EU applicants alike.';

    private const MASTER_NOTE = "Apply with a relevant bachelor's degree via the university's online portal. Programs typically assess your transcript and require proof of language proficiency (Italian or English, depending on the program); some also ask for a CV or short motivation letter.";

    private const TUITION_NOTE = 'Public Italian universities set tuition on a sliding scale tied to family income (ISEE) for most applicants, commonly from a few hundred to around €4,000/year; private universities and English-taught international tracks charge more. Confirm the exact figure for your situation on the official page.';

    private const WINDOW_OPEN = 'Applications typically open in July and close around September, for enrollment starting late September/October — check Universitaly and the university\'s own portal for exact dates each year.';

    private const WINDOW_RESTRICTED = "Entrance exam and application dates are published annually, usually between July and September for a fall start — check Universitaly and the university's page for the exact date, which changes every year.";

    /**
     * Each row: university slug, subject slug, degree level, official-ish
     * course title, language, duration in years, admission type, and an
     * optional note override appended to the standard admission text.
     */
    private const PROGRAMS = [
        ['university-of-bologna', 'computer-science', 'bachelor', 'Computer Science', 'Italian', 3, 'open'],
        ['university-of-bologna', 'computer-science', 'master', 'Computer Science', 'English', 2, 'open'],
        ['university-of-bologna', 'law', 'bachelor', 'Law (single-cycle)', 'Italian', 5, 'open', 'A single combined degree (no separate bachelor/master split) — 5 years total.'],
        ['university-of-bologna', 'medicine-and-surgery', 'bachelor', 'Medicine and Surgery (single-cycle)', 'Italian', 6, 'restricted', 'A single combined degree — 6 years total, among the most competitive entrance exams in Italy.'],

        ['sapienza-university-of-rome', 'architecture', 'bachelor', 'Architecture (single-cycle)', 'Italian', 5, 'restricted', 'A single combined degree — nationally coordinated entrance exam.'],
        ['sapienza-university-of-rome', 'medicine-and-surgery', 'bachelor', 'Medicine and Surgery (single-cycle)', 'English', 6, 'restricted', 'International (English-taught) track of the same restricted national exam.'],
        ['sapienza-university-of-rome', 'physics', 'master', 'Physics', 'English', 2, 'open'],
        ['sapienza-university-of-rome', 'international-relations', 'bachelor', 'International Relations and Diplomatic Affairs', 'Italian', 3, 'open'],

        ['politecnico-di-milano', 'mechanical-engineering', 'bachelor', 'Mechanical Engineering', 'Italian', 3, 'open'],
        ['politecnico-di-milano', 'mechanical-engineering', 'master', 'Mechanical Engineering', 'English', 2, 'open'],
        ['politecnico-di-milano', 'design', 'bachelor', 'Design', 'Italian', 3, 'open'],
        ['politecnico-di-milano', 'design', 'master', 'Product Service System Design', 'English', 2, 'open'],
        ['politecnico-di-milano', 'civil-engineering', 'bachelor', 'Civil Engineering', 'Italian', 3, 'open'],

        ['politecnico-di-torino', 'electrical-engineering', 'bachelor', 'Electrical Engineering', 'Italian', 3, 'open'],
        ['politecnico-di-torino', 'electrical-engineering', 'master', 'Electrical Engineering', 'English', 2, 'open'],
        ['politecnico-di-torino', 'civil-engineering', 'master', 'Civil Engineering', 'English', 2, 'open'],

        ['university-of-milan', 'biology', 'bachelor', 'Biological Sciences', 'Italian', 3, 'open'],
        ['university-of-milan', 'economics', 'master', 'Economics', 'English', 2, 'open'],
        ['university-of-milan', 'psychology', 'bachelor', 'Psychology', 'Italian', 3, 'restricted'],

        ['university-of-padua', 'psychology', 'bachelor', 'Psychological Science', 'Italian', 3, 'restricted'],
        ['university-of-padua', 'psychology', 'master', 'Psychology', 'English', 2, 'open'],
        ['university-of-padua', 'medicine-and-surgery', 'bachelor', 'Medicine and Surgery (single-cycle)', 'Italian', 6, 'restricted'],
        ['university-of-padua', 'mathematics', 'bachelor', 'Mathematics', 'Italian', 3, 'open'],

        ['university-of-florence', 'architecture', 'bachelor', 'Architecture (single-cycle)', 'Italian', 5, 'restricted'],
        ['university-of-florence', 'economics', 'bachelor', 'Economics', 'Italian', 3, 'open'],

        ['university-of-naples-federico-ii', 'mechanical-engineering', 'bachelor', 'Mechanical Engineering', 'Italian', 3, 'open'],
        ['university-of-naples-federico-ii', 'medicine-and-surgery', 'bachelor', 'Medicine and Surgery (single-cycle)', 'Italian', 6, 'restricted'],

        ['university-of-turin', 'economics', 'bachelor', 'Economics and Business', 'Italian', 3, 'open'],
        ['university-of-turin', 'international-relations', 'master', 'International Relations and Global Studies', 'English', 2, 'open'],

        ['university-of-pisa', 'computer-science', 'bachelor', 'Computer Science', 'Italian', 3, 'open'],
        ['university-of-pisa', 'computer-science', 'master', 'Computer Science', 'English', 2, 'open'],
        ['university-of-pisa', 'physics', 'bachelor', 'Physics', 'Italian', 3, 'open'],

        ['bocconi-university', 'economics', 'bachelor', 'Economics, Management and Computer Science', 'English', 3, 'restricted', "Private university — admission is via Bocconi's own selective application process, not the national numero-programmato exam."],
        ['bocconi-university', 'business-administration', 'bachelor', 'International Management', 'English', 3, 'restricted', "Private university — admission is via Bocconi's own selective application process."],
        ['bocconi-university', 'business-administration', 'master', 'Management', 'English', 2, 'restricted', "Private university — admission is via Bocconi's own selective application process."],

        ['university-of-trento', 'computer-science', 'master', 'Computer Science', 'English', 2, 'open'],
        ['university-of-trento', 'economics', 'bachelor', 'Economics and Management', 'Italian', 3, 'open'],

        ["ca-foscari-university-of-venice", 'economics', 'bachelor', 'Economics and Management', 'Italian', 3, 'open'],
        ["ca-foscari-university-of-venice", 'international-relations', 'bachelor', 'International Relations', 'English', 3, 'open'],

        ['university-of-genoa', 'civil-engineering', 'bachelor', 'Civil Engineering', 'Italian', 3, 'open'],
        ['university-of-genoa', 'physics', 'master', 'Physics', 'English', 2, 'open'],

        ['university-of-bari-aldo-moro', 'law', 'bachelor', 'Law (single-cycle)', 'Italian', 5, 'open', 'A single combined degree — 5 years total.'],
        ['university-of-bari-aldo-moro', 'medicine-and-surgery', 'bachelor', 'Medicine and Surgery (single-cycle)', 'Italian', 6, 'restricted'],
    ];

    public function run(): void
    {
        foreach (self::PROGRAMS as $row) {
            [$universitySlug, $subjectSlug, $degreeLevel, $name, $language, $durationYears, $admissionType] = $row;
            $extraNote = $row[7] ?? null;

            $university = University::where('slug', $universitySlug)->first();
            $subject = Subject::where('slug', $subjectSlug)->first();

            if (! $university || ! $subject) {
                continue;
            }

            $isRestricted = $admissionType === 'restricted';
            $admissionNotes = $isRestricted ? self::RESTRICTED_NOTE : self::OPEN_NOTE;

            if ($degreeLevel === 'master') {
                $admissionNotes = self::MASTER_NOTE;
            }

            if ($extraNote) {
                $admissionNotes .= ' '.$extraNote;
            }

            DegreeProgram::updateOrCreate(
                [
                    'university_id' => $university->id,
                    'subject_id' => $subject->id,
                    'degree_level' => $degreeLevel,
                    'name' => $name,
                ],
                [
                    'language' => $language,
                    'duration_years' => $durationYears,
                    'admission_type' => $admissionType,
                    'admission_notes' => $admissionNotes,
                    'tuition_note' => self::TUITION_NOTE,
                    'application_window_note' => $isRestricted ? self::WINDOW_RESTRICTED : self::WINDOW_OPEN,
                    'official_admission_url' => $university->website_url,
                    'source_url' => 'https://www.universitaly.it',
                    'last_verified_at' => now(),
                ],
            );
        }
    }
}
