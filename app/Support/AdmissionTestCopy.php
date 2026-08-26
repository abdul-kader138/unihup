<?php

namespace App\Support;

/**
 * Curated guidance on Italy's standardized admission tests for
 * "Restricted"-access degree programs — TOLC (CISIA), IMAT (English-taught
 * Medicine/Dentistry/Veterinary Medicine), and the "semestre filtro" reform
 * that replaced the old entrance exam for Italian-taught Medicine,
 * Dentistry and Veterinary Medicine from the 2024/25 intake. Verified
 * against cisiaonline.it (CISIA's own site), universitaly.it, and
 * secondary reporting on the semestre filtro reform while building this
 * feature — see SOURCE_URL and OFFICIAL_LINKS.
 *
 * Like AdmissionCopy/DocumentRecognitionCopy/VisaArrivalCopy, this is
 * uniform national process (which test applies to which subject area),
 * not per-university structured data, so it's curated text rather than
 * scraped/imported. Deliberately avoids stating specific yearly exam
 * dates or registration windows — those move every year and are published
 * fresh by CISIA/universitaly each cycle; state what's structurally true
 * instead and point to the official source for current dates.
 */
final class AdmissionTestCopy
{
    public const MODAL_NOTE = 'Programs flagged "Restricted" admission generally require passing a standardized entrance test before you can enrol — which one depends on the subject area. See the Admission Tests guide for which test applies and how it works.';

    public const OFFICIAL_LINKS = [
        'CISIA — TOLC overview' => 'https://www.cisiaonline.it/en/tolc/all-about-TOLC/what-is-the-TOLC',
        'CISIA — TOLC rules (retakes, validity, registration)' => 'https://www.cisiaonline.it/en/tolc/all-about-tolc/TOLC-rules',
        'CISIA — test dates' => 'https://www.cisiaonline.it/en/other-tests/test-arched/dates',
        'Universitaly — IMAT' => 'https://www.universitaly.it',
    ];

    public const SOURCE_URL = 'https://www.cisiaonline.it/en/tolc/all-about-TOLC/what-is-the-TOLC';

    public const SECTIONS = [
        [
            'heading' => 'Which test do I need?',
            'body' => "It depends entirely on the subject area, not the university — almost every Italian public university uses the same CISIA TOLC system for a given field, so the test is tied to what you're applying to study. The three tracks below cover nearly every restricted-entry program: TOLC for most fields, IMAT for English-taught medical programs, and the newer \"filter semester\" for Italian-taught Medicine, Dentistry and Veterinary Medicine. Always confirm on the specific program's own admission notice (bando) — a handful of programs, and most private/non-state universities, run their own separate entrance test outside this system.",
        ],
        [
            'heading' => 'TOLC (Test OnLine CISIA) — most restricted-entry programs',
            'body' => "TOLC is a standardized, computer-based admission test run by CISIA, a consortium most Italian public universities belong to. There isn't one TOLC — there are several versions, each built for a subject area: TOLC-I (engineering), TOLC-E (economics/social sciences), TOLC-F (pharmacy), TOLC-B (biology/life sciences), TOLC-S (general sciences), TOLC-AV (agriculture/veterinary), TOLC-SU (humanities), TOLC-PSI (psychology), TOLC-SPS (political/social sciences), and TOLC-LP (applied/professional-track degrees). Each is multiple-choice, delivered either on a university campus (TOLC@UNI) or remotely from home (TOLC@HOME), and costs a flat €35 registration fee. You get your result immediately for an on-campus sitting, or within about 48 hours for a remote one.",
            'checklist' => [
                'Confirm which TOLC type your specific program requires — it\'s named in the admission notice (bando), not left to guess',
                'Register on cisiaonline.it, choosing a delivery method (@UNI or @HOME), test date and location',
                'For TOLC@HOME, have valid photo ID ready to upload as part of registration',
                'Pay the €35 fee — the same fee regardless of TOLC type or delivery method',
            ],
            'note' => "Universities set their own pass thresholds and use of the score — some rank applicants by it, others just require a minimum to avoid an OFA (obbligo formativo aggiuntivo — an extra remedial course added to your first year). A low score doesn't always block enrollment outright; read your specific program's rules rather than assuming.",
            'critical' => false,
        ],
        [
            'heading' => 'IMAT — English-taught Medicine, Dentistry & Veterinary Medicine',
            'body' => 'IMAT (International Medical Admissions Test) is the entrance exam for English-language Medicine and Surgery, Dentistry, and Veterinary Medicine programs — the tracks built specifically for international students. It is administered once a year, on the same date at every participating Italian university and at several test centres abroad, and organized centrally through the Ministry via the Universitaly portal rather than by individual universities. The test is 100 minutes, 60 multiple-choice questions covering logical reasoning, general knowledge, biology, chemistry, physics and mathematics, with 1.5 points for each correct answer and a 0.4-point penalty for each incorrect one (unanswered questions score zero) — a maximum of 90 points.',
            'checklist' => [
                'Register and pay through universitaly.it — not through CISIA or the university directly',
                'Check the current year\'s registration window on universitaly.it — the Ministry has stated in past cycles that this deadline is not extended for missed payments or technical issues',
                'Rank your preferred universities in order on the application — placement is by national ranking against available seats, not a per-university pass/fail',
            ],
            'note' => "IMAT is unaffected by the semestre filtro reform below — it still uses a single entrance exam, precisely because open access wouldn't be workable for the volume of international applicants this track receives.",
            'critical' => true,
        ],
        [
            'heading' => 'Italian-taught Medicine, Dentistry & Veterinary Medicine — the "semestre filtro"',
            'body' => "From the 2024/25 intake, Italy replaced the old single entrance exam for Italian-taught Medicine, Dentistry and Veterinary Medicine with an open-access \"filter semester\" (semestre filtro). You can enrol in the first semester without any entrance test, but you must simultaneously enrol in a related biomedical, health, pharmaceutical, or veterinary degree at the same time (at no extra cost) as a fallback. During the semester, standardized national exams in biology, chemistry and physics are set and graded with the same criteria everywhere, with two attempts per subject. Your combined performance places you in a national merit ranking, which — together with the list of universities you rank by preference (a minimum of five) and each university's seat count — determines whether and where you continue into the second semester.",
            'checklist' => [
                'You may attempt the filter semester a maximum of three times over your lifetime',
                'You must also be enrolled in a genuine fallback degree during the filter semester — this isn\'t optional paperwork, it\'s where you land if the ranking doesn\'t place you',
                'List at least five universities in your order of preference for the medicine/dentistry/vet ranking',
            ],
            'note' => 'This route applies only to Italian-taught programs. If you\'re applying to an English-taught Medicine, Dentistry or Veterinary Medicine program, use IMAT above instead.',
            'critical' => false,
        ],
    ];
}
