<?php

namespace App\Services\Universities\Enrichers;

use App\Contracts\DataEnricher;
use App\Contracts\EnrichmentResult;
use App\Models\DegreeProgram;
use App\Support\AdmissionCopy;

/**
 * Fills admission_notes/tuition_note/application_window_note on degree
 * programs that have none — chiefly the bulk MUR/USTAT-imported rows,
 * which carry structural facts (degree level, admission_type) but no
 * explanatory text, unlike the hand-curated DegreeProgramSeeder rows.
 *
 * The text itself isn't program-specific — no open dataset publishes
 * per-program tuition/deadlines (see MurUstatImporter's class doc comment)
 * — so this applies the same general guidance DegreeProgramSeeder already
 * uses, chosen by degree_level/admission_type. It only touches rows where
 * the field is empty, so it never clobbers a more specific note a human
 * editor added in Filament.
 */
class AdmissionContentEnricher implements DataEnricher
{
    public function enrich(): EnrichmentResult
    {
        $updated = 0;

        DegreeProgram::query()
            ->where(function ($query) {
                $query->whereNull('admission_notes')
                    ->orWhereNull('tuition_note')
                    ->orWhereNull('application_window_note');
            })
            ->select(['id', 'degree_level', 'admission_type', 'admission_notes', 'tuition_note', 'application_window_note'])
            ->chunkById(500, function ($programs) use (&$updated) {
                foreach ($programs as $program) {
                    $program->forceFill([
                        'admission_notes' => $program->admission_notes
                            ?? AdmissionCopy::admissionNotes($program->degree_level, $program->admission_type),
                        'tuition_note' => $program->tuition_note ?? AdmissionCopy::TUITION_NOTE,
                        'application_window_note' => $program->application_window_note
                            ?? AdmissionCopy::applicationWindowNote($program->admission_type),
                    ])->save();

                    $updated++;
                }
            });

        return new EnrichmentResult(
            updated: $updated,
            skipped: 0,
            summary: "Filled admission/tuition/deadline guidance text on {$updated} degree program(s) missing it.",
        );
    }
}
