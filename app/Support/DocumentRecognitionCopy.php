<?php

namespace App\Support;

/**
 * Curated guidance on getting a foreign qualification recognized for
 * Italian university admission (Dichiarazione di Valore / CIMEA), verified
 * against CIMEA's own site, MIM (mim.gov.it/en/dichiarazione-di-valore),
 * and studyinitaly.esteri.it while building this feature.
 *
 * This is uniform national policy, not per-university structured data —
 * there's nothing to scrape or import per institution, so (like
 * AdmissionCopy/LanguageProficiencyCopy) it's maintained as curated text
 * with an official source, not automated. It does change occasionally
 * (CIMEA's online "Diplome" self-service is a relatively recent
 * alternative to the embassy-only process) — re-verify before assuming
 * this is still current.
 */
final class DocumentRecognitionCopy
{
    public const MODAL_NOTE = 'If your previous diploma or degree was awarded outside Italy, most programs will ask you to document how it compares to the Italian system before you can enrol — either a Dichiarazione di Valore from an Italian embassy/consulate, or a CIMEA statement requested online. Which one a given program accepts varies — check its official admission page.';

    public const OFFICIAL_LINKS = [
        'CIMEA (Italy\'s ENIC-NARIC center)' => 'https://cimea.it',
        'CIMEA Diplome (online application portal)' => 'https://mywallet.cimea-diplome.it',
        'MIM — Dichiarazione di Valore' => 'https://www.mim.gov.it/en/dichiarazione-di-valore',
        'Study in Italy — enrollment procedures' => 'https://studyinitaly.esteri.it/Static/Procedureiscrizione',
    ];

    public const SOURCE_URL = 'https://www.mim.gov.it/en/dichiarazione-di-valore';

    public const SECTIONS = [
        [
            'heading' => 'Dichiarazione di Valore (DOV)',
            'body' => "Issued by the Italian embassy or consulate in the country where you earned your qualification. It isn't a formal recognition — it's an official document explaining your institution, course duration, admission requirements, and grading, in terms Italian universities can evaluate. Your diploma and transcript must be legalized (or apostilled, if your country is party to the Hague Convention) before you can request it. Apply directly through the embassy or consulate — requirements and processing time vary by country.",
        ],
        [
            'heading' => "CIMEA's Statement of Comparability & Statement of Verification",
            'body' => "CIMEA is Italy's national ENIC-NARIC center for qualification recognition. Its Statement of Comparability confirms your qualification's level against the Bologna Process / European Qualifications Framework (roughly: bachelor's-equivalent, master's-equivalent, etc.) — a non-binding opinion, not a legal recognition. Its separate Statement of Verification confirms the document itself is authentic, and says nothing about its level. Both are applied for online via CIMEA's Diplome platform, with no need to visit an embassy — standard processing is 15-30 working days, express is 5-10, once your documents and payment are submitted. Your diploma still needs to be legalized or apostilled first.",
        ],
        [
            'heading' => 'Which one do I need?',
            'body' => "That's decided entirely by the specific university and program's own admission notice (bando), not a single national rule. Many universities now accept a CIMEA statement instead of, or alongside, a DOV — especially for master's and PhD applicants — but this isn't universal. Always confirm on the official admission page for the specific program before requesting either one; picking the wrong one costs real weeks and money to redo.",
        ],
        [
            'heading' => 'Before you start',
            'body' => "Get your diploma and transcript legalized or apostilled in your home country first — neither DOV nor a CIMEA statement can be requested without it. Have certified translations ready if the university or consulate asks for them. Start early: both processes commonly take several weeks, and application deadlines don't move for you.",
        ],
    ];
}
