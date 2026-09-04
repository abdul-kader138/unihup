<?php

namespace App\Console\Commands;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicateSubjects extends Command
{
    protected $signature = 'subjects:merge-duplicates {--apply : Apply the merge; without this option only a report is generated}';

    protected $description = 'Report or safely merge subjects sharing the same canonical name';

    public function handle(): int
    {
        $groups = Subject::query()->whereNotNull('canonical_name')->get()
            ->groupBy(fn (Subject $subject) => mb_strtolower(trim($subject->canonical_name)))
            ->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No duplicate canonical subject names found.');

            return self::SUCCESS;
        }

        $applied = 0;
        foreach ($groups as $canonicalName => $subjects) {
            $keeper = $subjects->sortBy('id')->first();
            $duplicates = $subjects->where('id', '!=', $keeper->id);
            $programCount = DegreeProgram::whereIn('subject_id', $duplicates->pluck('id'))->count();
            $blocked = $this->hasProgramConflict($keeper, $duplicates);

            $this->line(sprintf(
                '%s: keep #%d "%s"; merge #%s (%d programs)%s',
                $canonicalName,
                $keeper->id,
                $keeper->name,
                $duplicates->pluck('id')->implode(', #'),
                $programCount,
                $blocked ? ' — BLOCKED: program conflict' : '',
            ));

            if (! $this->option('apply') || $blocked) {
                continue;
            }

            DB::transaction(function () use ($duplicates, $keeper) {
                DegreeProgram::whereIn('subject_id', $duplicates->pluck('id'))
                    ->update(['subject_id' => $keeper->id]);
                User::whereIn('preferred_subject_id', $duplicates->pluck('id'))
                    ->update(['preferred_subject_id' => $keeper->id]);
                Subject::whereIn('id', $duplicates->pluck('id'))->delete();
            });

            $applied += $duplicates->count();
        }

        $this->info($this->option('apply')
            ? "Merged {$applied} duplicate subject record(s)."
            : 'Dry run only. Re-run with --apply after reviewing the report.');

        return self::SUCCESS;
    }

    private function hasProgramConflict(Subject $keeper, $duplicates): bool
    {
        foreach (DegreeProgram::whereIn('subject_id', $duplicates->pluck('id'))->get() as $program) {
            if (DegreeProgram::where('subject_id', $keeper->id)
                ->where('university_id', $program->university_id)
                ->where('degree_level', $program->degree_level)
                ->where('name', $program->name)
                ->exists()) {
                return true;
            }
        }

        return false;
    }
}
