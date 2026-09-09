<?php

namespace Database\Seeders;

use App\Models\Deadline;
use Illuminate\Database\Seeder;

/**
 * The well-known nationwide dates every applicant to an Italian university
 * runs into. Curated, indicative windows — staff refine exact dates each
 * cycle in the Deadlines resource. Precision is deliberately "window" /
 * "month" for most, so the reminder command (which only fires on "day")
 * doesn't send noise off an approximate date.
 */
class DeadlineSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = '2026/2027';

        $rows = [
            [
                'title' => 'Universitaly pre-enrolment opens',
                'category' => 'pre_enrolment',
                'due_at' => '2026-06-01 09:00:00',
                'due_precision' => 'month',
                'scope_type' => Deadline::SCOPE_GLOBAL,
                'description' => 'Non-EU applicants must complete pre-enrolment (preiscrizione) on universitaly.it before a visa can be issued. Universities set their own closing dates.',
                'url' => 'https://www.universitaly.it',
            ],
            [
                'title' => 'Typical university application deadline (autumn intake)',
                'category' => 'application',
                'due_at' => '2026-07-15 23:59:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_GLOBAL,
                'description' => 'Most universities close international applications for the autumn intake between May and August — check each program page.',
            ],
            [
                'title' => 'TOLC registration for summer sessions',
                'category' => 'test_registration',
                'due_at' => '2026-05-01 23:59:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_ADMISSION_TEST,
                'description' => 'Register on cisiaonline.it a few weeks before your chosen TOLC sitting. Seats fill up.',
                'url' => 'https://www.cisiaonline.it',
            ],
            [
                'title' => 'IMAT (English-taught Medicine) test day',
                'category' => 'test_sitting',
                'due_at' => '2026-09-15 09:00:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_ADMISSION_TEST,
                'description' => 'IMAT is usually held in mid-September; registration closes several weeks earlier via Universitaly.',
                'url' => 'https://www.universitaly.it',
            ],
            [
                'title' => 'Regional scholarship (DSU) application window',
                'category' => 'scholarship',
                'due_at' => '2026-08-31 23:59:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_GLOBAL,
                'description' => 'Right-to-study scholarships and housing are applied for per region, typically July–September, usually before you have an admission result.',
            ],
            [
                'title' => 'Apply for the Type D student visa',
                'category' => 'visa',
                'due_at' => '2026-08-01 09:00:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_GLOBAL,
                'description' => 'Book your consulate appointment as soon as pre-enrolment is validated — processing can take several weeks and is the main risk to your start date.',
                'url' => 'https://vistoperitalia.esteri.it/home.aspx',
            ],
            [
                'title' => 'Permesso di soggiorno — apply within 8 working days of arrival',
                'category' => 'enrolment',
                'due_at' => '2026-10-01 09:00:00',
                'due_precision' => 'window',
                'scope_type' => Deadline::SCOPE_GLOBAL,
                'description' => 'Submit the residence-permit kit at a Poste Italiane Sportello Amico within 8 working days of entering Italy.',
                'url' => 'https://www.poste.it/guida-rilascio-e-rinnovo-permesso-di-soggiorno',
            ],
        ];

        foreach ($rows as $row) {
            Deadline::updateOrCreate(
                ['title' => $row['title'], 'cycle_label' => $cycle],
                array_merge($row, [
                    'cycle_label' => $cycle,
                    'is_active' => true,
                    'last_verified_at' => now(),
                ]),
            );
        }
    }
}
