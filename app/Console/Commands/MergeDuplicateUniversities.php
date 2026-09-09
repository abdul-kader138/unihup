<?php

namespace App\Console\Commands;

use App\Models\DegreeProgram;
use App\Models\University;
use App\Models\UniversityRanking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicateUniversities extends Command
{
    protected $signature = 'universities:merge-duplicates {--apply : Apply the merge; without this option only a report is generated}';

    protected $description = 'Report or safely merge universities sharing the same canonical name';

    public function handle(): int
    {
        $groups = University::query()
            ->whereNotNull('canonical_name')
            ->orderBy('canonical_name')
            ->get()
            ->groupBy(fn (University $university) => mb_strtolower(trim($university->canonical_name)))
            ->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No duplicate canonical university names found.');

            return self::SUCCESS;
        }

        $applied = 0;
        foreach ($groups as $canonicalName => $universities) {
            $keeper = $universities->sortBy('id')->first();
            $duplicates = $universities->where('id', '!=', $keeper->id);
            $programCount = DegreeProgram::whereIn('university_id', $duplicates->pluck('id'))->count();
            $rankingCount = UniversityRanking::whereIn('university_id', $duplicates->pluck('id'))->count();
            $programConflicts = $this->programConflictCount($keeper, $duplicates);
            $rankingConflicts = $this->rankingConflictCount($keeper, $duplicates);

            $this->line(sprintf(
                '%s: keep #%d "%s"; merge #%s (%d programs, %d rankings)%s',
                $canonicalName,
                $keeper->id,
                $keeper->name,
                $duplicates->pluck('id')->implode(', #'),
                $programCount,
                $rankingCount,
                ($programConflicts + $rankingConflicts) > 0
                    ? sprintf(' — %d program conflict(s), %d ranking conflict(s) will be deduplicated', $programConflicts, $rankingConflicts)
                    : '',
            ));

            if (! $this->option('apply')) {
                continue;
            }

            DB::transaction(function () use ($duplicates, $keeper) {
                foreach (DegreeProgram::whereIn('university_id', $duplicates->pluck('id'))->get() as $program) {
                    $existing = DegreeProgram::where('university_id', $keeper->id)
                        ->where('subject_id', $program->subject_id)
                        ->where('degree_level', $program->degree_level)
                        ->where('name', $program->name)
                        ->first();

                    if ($existing) {
                        $this->copyMissingProgramFields($existing, $program);
                        $program->delete();
                    } else {
                        $program->update(['university_id' => $keeper->id]);
                    }
                }

                foreach (UniversityRanking::whereIn('university_id', $duplicates->pluck('id'))->get() as $ranking) {
                    $existing = UniversityRanking::where('university_id', $keeper->id)
                        ->where('edition', $ranking->edition)
                        ->first();

                    if ($existing) {
                        $this->copyMissingRankingFields($existing, $ranking);
                        $ranking->delete();
                    } else {
                        $ranking->update(['university_id' => $keeper->id]);
                    }
                }

                University::whereIn('id', $duplicates->pluck('id'))->delete();
            });

            $applied += $duplicates->count();
        }

        if ($this->option('apply')) {
            $this->info("Merged {$applied} duplicate university record(s).");
        } else {
            $this->info('Dry run only. Re-run with --apply after reviewing the report.');
        }

        return self::SUCCESS;
    }

    private function programConflictCount(University $keeper, $duplicates): int
    {
        $count = 0;

        foreach (DegreeProgram::whereIn('university_id', $duplicates->pluck('id'))->get() as $program) {
            if (DegreeProgram::where('university_id', $keeper->id)
                ->where('subject_id', $program->subject_id)
                ->where('degree_level', $program->degree_level)
                ->where('name', $program->name)
                ->exists()) {
                $count++;
            }
        }

        return $count;
    }

    private function rankingConflictCount(University $keeper, $duplicates): int
    {
        $count = 0;

        foreach (UniversityRanking::whereIn('university_id', $duplicates->pluck('id'))->get() as $ranking) {
            if (UniversityRanking::where('university_id', $keeper->id)
                ->where('edition', $ranking->edition)
                ->exists()) {
                $count++;
            }
        }

        return $count;
    }

    private function copyMissingProgramFields(DegreeProgram $keeper, DegreeProgram $duplicate): void
    {
        $updates = [];
        foreach (['language', 'duration_years', 'admission_type', 'admission_notes', 'tuition_note', 'application_window_note', 'official_admission_url', 'source_url', 'last_verified_at'] as $field) {
            if (blank($keeper->{$field}) && filled($duplicate->{$field})) {
                $updates[$field] = $duplicate->{$field};
            }
        }

        if ($updates) {
            $keeper->update($updates);
        }
    }

    private function copyMissingRankingFields(UniversityRanking $keeper, UniversityRanking $duplicate): void
    {
        $updates = [];
        foreach (['category', 'position', 'score_services', 'score_scholarships', 'score_facilities', 'score_communication_digital', 'score_internationalization', 'score_employability', 'overall_score', 'source_url', 'last_verified_at'] as $field) {
            if (blank($keeper->{$field}) && filled($duplicate->{$field})) {
                $updates[$field] = $duplicate->{$field};
            }
        }

        if ($updates) {
            $keeper->update($updates);
        }
    }
}
