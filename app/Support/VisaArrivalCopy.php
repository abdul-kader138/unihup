<?php

namespace App\Support;

/**
 * Curated guidance on the immigration side of studying in Italy — pre-
 * enrollment, the Type D student visa, health insurance, the residence
 * permit, the tax code, and the Marco Polo/Turandot pathway — verified
 * against vistoperitalia.esteri.it, an Italian embassy's own published
 * consular fee page (ambankara.esteri.it), Universitaly's pre-enrollment
 * instructions, Poste Italiane's permesso di soggiorno guide, Polizia di
 * Stato's own means-of-subsistence table, and university international-
 * office pages for Marco Polo/Turandot, while building this feature.
 *
 * Distinct from App\Support\DocumentRecognitionCopy: that's about getting
 * a foreign qualification recognized for admission purposes; this is
 * about the separate immigration process to actually enter and stay in
 * Italy. Same reasoning as that class for why this is curated text with
 * an official source rather than automated — it's uniform national
 * process, not per-university data.
 *
 * Deliberately does NOT state a specific euro figure for the study-visa
 * financial-means requirement: Polizia di Stato's own published table
 * (linked below) is dated 2018 and structured for short Schengen-type
 * stays (a flat amount plus a per-day rate), not long-stay study visas,
 * and the "~€538/month" figure that circulates on third-party sites
 * couldn't be traced back to that or any other primary source when this
 * was written — republishing an unverifiable number here would be worse
 * than pointing to where to get the real one.
 */
final class VisaArrivalCopy
{
    public const MODAL_NOTE = 'Non-EU applicants also need to handle immigration separately from admission: pre-enroll on Universitaly, then apply for a Type D student visa, then register for a residence permit within 8 working days of arriving in Italy.';

    public const OFFICIAL_LINKS = [
        'Visto per l\'Italia (visa portal)' => 'https://vistoperitalia.esteri.it/home.aspx',
        'Find your competent embassy/consulate' => 'https://www.esteri.it/en/ministero/struttura/rete-diplomatica/',
        'Universitaly (pre-enrollment)' => 'https://www.universitaly.it',
        'Poste Italiane — permesso di soggiorno guide' => 'https://www.poste.it/guida-rilascio-e-rinnovo-permesso-di-soggiorno',
        'Polizia di Stato — means-of-subsistence table' => 'https://www.poliziadistato.it/articolo/tabella-per-la-determinazione-dei-mezzi-di-sussistenza-richiesti-per-l-ingresso-nel-territorio-nazionale.',
        'Agenzia delle Entrate — codice fiscale' => 'https://www.agenziaentrate.gov.it',
    ];

    public const SOURCE_URL = 'https://vistoperitalia.esteri.it/home.aspx';

    /**
     * Grouped display order — the blade view renders one heading per phase,
     * with the steps inside it in sequence.
     */
    public const PHASES = [
        'before' => 'Before you travel',
        'arrival' => 'After you arrive in Italy',
        'alternative' => 'Alternative pathway',
        'after' => 'After you graduate',
    ];

    /**
     * Each entry: step number, phase (key into PHASES), a heroicon name,
     * heading, who it applies to, an intro paragraph, an optional
     * document/action checklist, an optional caveat note, and whether
     * that note is a hard deadline/eligibility risk (shown as a warning)
     * rather than a routine caveat (shown as a neutral tip).
     */
    public const SECTIONS = [
        [
            'step' => 1,
            'phase' => 'before',
            'icon' => 'heroicon-o-clipboard-document-check',
            'heading' => 'Pre-enroll on Universitaly',
            'who' => 'Almost all non-EU applicants (exceptions: Marco Polo/Turandot students — see the alternative pathway below).',
            'body' => "Before you can apply for a visa, you must complete pre-enrollment (preiscrizione) on the Universitaly portal — the Ministry of University's own system, separate from any individual university's application. A study visa can only be requested once your university has validated your Universitaly application, so start this the moment the portal opens for your intake — universities set their own pre-enrollment deadlines (often around September for a fall start), and running out of time here is the single most common reason students miss the intake.",
            'checklist' => [
                'A valid passport (check its expiry covers your full intended stay)',
                'Your qualifying diploma/degree and transcripts',
                'Proof of admission or the program you intend to apply to',
                'A passport-style photo, per the portal\'s current spec',
            ],
            'note' => 'For many non-EU applicants, Universitaly automatically issues a Codice Fiscale (Italian tax code) during this step — check yours before assuming you need the Codice Fiscale step below.',
            'critical' => false,
        ],
        [
            'step' => 2,
            'phase' => 'before',
            'icon' => 'heroicon-o-globe-europe-africa',
            'heading' => 'Apply for the Type D national visa',
            'who' => 'Every non-EU applicant staying more than 90 days — effectively every full degree program.',
            'body' => 'Apply at the Italian embassy or consulate with jurisdiction over your place of residence, only after your Universitaly pre-enrollment is validated. Prior university acceptance does not guarantee visa issuance — that decision belongs entirely to the consulate, and border officers can still question your documents on arrival even with an approved visa.',
            'checklist' => [
                'Universitaly pre-enrollment validation confirmation',
                'Proof of sufficient financial means for your stay — the exact threshold is set by the Ministry of Interior and interpreted per consulate, not a single published number (see note)',
                'Proof of accommodation in Italy',
                'Valid health insurance covering your full stay (see the next step)',
                'The consular visa fee — commonly around €50 for a national study visa, but consular fees are re-set quarterly against exchange rates, so confirm the current amount with your specific consulate',
            ],
            'note' => "Polizia di Stato's own means-of-subsistence table is dated 2018 and built for short Schengen-type stays (a flat amount plus a per-day rate), not specifically for year-long study visas — treat the figure your consulate quotes you as authoritative over anything else you read online, including this page.",
            'critical' => true,
        ],
        [
            'step' => 3,
            'phase' => 'before',
            'icon' => 'heroicon-o-heart',
            'heading' => 'Get health insurance before you travel',
            'who' => 'Every visa applicant — this is a visa precondition, not an after-arrival task.',
            'body' => "Valid health insurance covering your full stay is required for the visa itself, so arrange private international student cover before applying — you can't yet be enrolled in Italy's national health service (SSN) at that point, since SSN enrollment itself requires an Italian residence permit you don't have yet.",
            'checklist' => [
                "Confirm the policy's coverage dates span your entire visa validity, not just your travel dates",
                'Keep a printable copy — both the visa application and, later, the permesso di soggiorno kit require a physical copy',
            ],
            'note' => 'After you arrive and hold a residence permit for study, you can optionally switch to voluntary SSN enrollment for a flat annual contribution (commonly cited around €700/year, set annually — confirm the current figure with your local ASL), which is often cheaper than continuing private cover for a multi-year program.',
            'critical' => false,
        ],
        [
            'step' => 4,
            'phase' => 'arrival',
            'icon' => 'heroicon-o-identification',
            'heading' => 'Apply for your permesso di soggiorno within 8 working days',
            'who' => 'Every non-EU student, immediately after arriving in Italy.',
            'body' => 'This is separate from the visa, and easy to miss the deadline on: within 8 working days of arriving in Italy, you must request a residence permit for study purposes using the yellow-banded kit ("kit giallo") from any Italian post office (look for the "Sportello Amico" desk).',
            'checklist' => [
                'The completed kit giallo form',
                'A photocopy of your passport and visa',
                "A photocopy of valid health insurance covering the permit's full duration",
                'Proof of financial means to support yourself in Italy',
                'A €16 revenue stamp (marca da bollo), available at any tobacconist (tabaccheria)',
            ],
            'note' => 'Missing this 8-day window can jeopardize your legal status in Italy — put it on the calendar before you even board your flight, not after you land.',
            'critical' => true,
        ],
        [
            'step' => 5,
            'phase' => 'arrival',
            'icon' => 'heroicon-o-hashtag',
            'heading' => "Get your Codice Fiscale, if you don't already have one",
            'who' => 'Anyone Universitaly didn\'t already issue one to during pre-enrollment (see step 1).',
            'body' => "Italy's tax code is required for almost everything else — opening a bank account, signing a lease, a phone contract, and finalizing university enrollment.",
            'checklist' => [
                "Request it from the Agenzia delle Entrate (Italy's tax agency), or",
                "Depending on your permit type, the local Questura or Prefecture's immigration office",
            ],
            'note' => "Many universities run dedicated sessions for incoming international students to issue these in bulk — check with your university's international office before doing it yourself.",
            'critical' => false,
        ],
        [
            'step' => 6,
            'phase' => 'alternative',
            'icon' => 'heroicon-o-academic-cap',
            'heading' => 'Marco Polo & Turandot programmes (Chinese students only)',
            'who' => 'Chinese nationals applying to an Italian university (Marco Polo) or AFAM arts/music/dance institution (Turandot).',
            'body' => 'A bilateral agreement between Italy and China (coordinated by the Conference of Italian University Rectors) that replaces the standalone visa process above with a structured pathway: pre-enrollment at the Italian Embassy in China, followed by a dedicated 10-month, ~800-hour Italian language course at a partner school in Italy (starting around November), before matriculating at the chosen university or AFAM institution the following academic year.',
            'checklist' => [
                'Pre-enroll at the Italian Embassy in China by the program\'s deadline (commonly end of August) — specify both your target university/institution and your chosen language-course school',
                'Complete the Italian language course and reach at least CEFR B1 level',
                'Obtain a B1 language certificate from a recognized body (CELI, CILS, PLIDA, or CERTIT) to matriculate',
            ],
            'note' => 'This pathway still ends with a Type D visa and, after arrival, the same permesso di soggiorno process above — Marco Polo/Turandot changes how you get admitted and language-qualified, not whether you need the rest of this guide.',
            'critical' => false,
        ],
        [
            'step' => 7,
            'phase' => 'after',
            'icon' => 'heroicon-o-briefcase',
            'heading' => 'Staying to look for work',
            'who' => 'Non-EU graduates of an Italian university who don\'t already have a job lined up.',
            'body' => 'Italian law (Legge 99/2013) lets non-EU graduates convert a study residence permit into a job-seeking permit (permesso di soggiorno per attesa occupazione), valid for 9-12 months, to look for work in Italy without leaving. This is a right tied to holding the degree, not something you need to qualify for competitively.',
            'checklist' => [
                'Register with your local employment center (Centro per l\'Impiego) as actively job-seeking',
                'Request the conversion before your study permit expires — include proof of your degree and the employment-center registration in the kit sent to the Questura',
            ],
            'note' => 'If you secure a job offer instead, the conversion goes straight to a work permit — your employer needs to provide a proposed residence contract (contratto di soggiorno) as part of that, a different and separate process from the job-seeking conversion above.',
            'critical' => false,
        ],
    ];
}
