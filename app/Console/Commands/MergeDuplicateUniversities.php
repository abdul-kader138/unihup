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
            $blocked = $this->hasProgramConflict($keeper, $duplicates) || $this->hasRankingConflict($keeper, $duplicates);

            $this->line(sprintf(
                '%s: keep #%d "%s"; merge #%s (%d programs, %d rankings)%s',
                $canonicalName,
                $keeper->id,
                $keeper->name,
                $duplicates->pluck('id')->implode(', #'),
                $programCount,
                $rankingCount,
                $blocked ? ' — BLOCKED: relationship conflict' : '',
            ));

            if (! $this->option('apply') || $blocked) {
                continue;
            }

            DB::transaction(function () use ($duplicates, $keeper) {
                DegreeProgram::whereIn('university_id', $duplicates->pluck('id'))
                    ->update(['university_id' => $keeper->id]);
                UniversityRanking::whereIn('university_id', $duplicates->pluck('id'))
                    ->update(['university_id' => $keeper->id]);
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

    private function hasProgramConflict(University $keeper, $duplicates): bool
    {
        foreach (DegreeProgram::whereIn('university_id', $duplicates->pluck('id'))->get() as $program) {
            if (DegreeProgram::where('university_id', $keeper->id)
                ->where('subject_id', $program->subject_id)
                ->where('degree_level', $program->degree_level)
                ->where('name', $program->name)
                ->exists()) {
                return true;
            }
        }

        return false;
    }

    private function hasRankingConflict(University $keeper, $duplicates): bool
    {
        foreach (UniversityRanking::whereIn('university_id', $duplicates->pluck('id'))->get() as $ranking) {
            if (UniversityRanking::where('university_id', $keeper->id)
                ->where('edition', $ranking->edition)
                ->exists()) {
                return true;
            }
        }

        return false;
    }
}
