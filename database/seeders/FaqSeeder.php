<?php

namespace Database\Seeders;

use App\Models\FaqEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['General', 'What is UniHup?', 'UniHup helps international students find, compare and apply to university programs in Italy. Save programs to **My Applications**, track your **document vault** and **deadlines**, follow the personalised **journey checklist**, and read the guides on admission tests, document recognition and the visa process.'],
            ['Choosing a program', 'What does "open" vs "restricted" admission mean?', '**Open access** (libero accesso) programs admit anyone who meets the entry requirements, sometimes with a non-blocking placement test. **Restricted** programs (numero programmato) have a fixed number of seats and require a competitive entrance test such as TOLC or IMAT — see the Admission Tests guide.'],
            ['Choosing a program', 'Can I study in English?', "Yes — many Italian universities run English-taught bachelor's and master's degrees. Filter by language on **Find Universities**. English-taught programs usually ask for IELTS 6.0–6.5 / TOEFL 80–90 unless you have a prior degree taught in English."],
            ['Applications', 'How many programs should I apply to?', 'There is no single rule, but 4–6 is common: a mix of ambitious, realistic and safe choices. Each university has its own portal and deadline — track them all in **My Applications** and **My Deadlines**.'],
            ['Admission tests', 'Which admission test do I need?', "It depends on the subject, not the university. Most restricted programs use a subject-specific **TOLC** (via CISIA); English-taught Medicine/Dentistry/Vet use **IMAT** (via Universitaly); Italian-taught Medicine now uses the *semestre filtro*. The program's admission notice (bando) states which one."],
            ['Documents & recognition', 'What is a Dichiarazione di Valore?', 'The *Dichiarazione di Valore* (DoV) is a statement issued by the Italian embassy/consulate in the country where you earned your qualification, explaining it in terms Italian universities can evaluate. Many universities now also accept a **CIMEA** statement instead. Start early — this is the slowest step. See the Doc Recognition guide.'],
            ['Visa & arrival', 'Do I need a visa to study in Italy?', 'EU/EEA/Swiss citizens do not. Everyone else needs a **Type D national student visa**, which requires completing pre-enrolment on Universitaly first, and then applying for a *permesso di soggiorno* within 8 working days of arriving. See the Visa & Arrival guide.'],
            ['Visa & arrival', 'What is a codice fiscale and how do I get one?', "It's the Italian tax code, needed to sign a lease, open a bank account or register with the health service. Universitaly often issues one automatically during pre-enrolment; otherwise get it from the Agenzia delle Entrate or an Italian consulate."],
            ['Money & scholarships', 'How much does it cost to study in Italy?', 'Public-university tuition scales with your household **ISEE / ISEE Parificato** — from near-zero in the no-tax band up to roughly €2,800–4,200/year at the top. Add living costs of about €700–1,400/month depending on the city. Use the **Cost Estimator** for a personalised range.'],
            ['Money & scholarships', 'What is the ISEE Parificato?', "If your family isn't resident in Italy, a normal ISEE can't be calculated for you, so you need the **ISEE Parificato** — a foreign-income equivalent produced by a CAF affiliated with your university from translated income/asset documents. Miss the deadline (often ~30 September) and you're placed in the top fee band automatically."],
            ['Money & scholarships', 'What are regional (DSU) scholarships?', 'Right-to-study benefits — income-tested scholarships, subsidised housing and meal plans — administered per region (e.g. DSU Lombardia, ER.GO, EDISU), not nationally. They have their own early deadlines, usually independent of your admission result. Flag scholarship interest in your profile to see these steps.'],
            ['Living in Italy', 'When should I start looking for housing?', 'As early as possible — months ahead in Milan, Bologna and Florence. Check university residence halls and the regional right-to-study body first, then shared flats. See the **City Guides** for neighbourhood tips and typical rents.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            FaqEntry::updateOrCreate(
                ['slug' => Str::slug(Str::limit($question, 60, ''))],
                [
                    'question' => $question,
                    'answer' => $answer,
                    'category' => $category,
                    'sort' => $i,
                    'is_published' => true,
                ],
            );
        }
    }
}
