<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigestMail;
use App\Models\Deadline;
use App\Models\JourneyProgress;
use App\Models\ScholarshipTracker;
use App\Models\Setting;
use App\Models\ShortlistItem;
use App\Models\User;
use App\Support\JourneyTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Weekly "your week on UniHup" digest — upcoming deadlines, next checklist
 * steps, stalled applications. Sent once per ISO week (tracked on
 * users.weekly_digest_sent_at) to students who have a shortlist and haven't
 * opted out. Students with nothing worth an email are skipped.
 */
class SendWeeklyDigest extends Command
{
    protected $signature = 'unihup:send-weekly-digest {--dry-run} {--force : Ignore the once-per-week guard}';

    protected $description = 'Send the weekly student digest (deadlines, checklist, applications)';

    public function handle(): int
    {
        if (! (bool) Setting::get('deadline_reminders_enabled', true)) {
            $this->info('Reminders are disabled in System Settings — nothing sent.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $weekStart = now()->startOfWeek();
        $sent = 0;
        $skipped = 0;

        $users = User::query()
            ->where('deadline_reminders_opt_out', false)
            ->whereNotNull('email_verified_at')
            ->whereHas('shortlistItems')
            ->when(! $force, fn ($q) => $q->where(function ($q) use ($weekStart) {
                $q->whereNull('weekly_digest_sent_at')->orWhere('weekly_digest_sent_at', '<', $weekStart);
            }))
            ->get();

        foreach ($users as $user) {
            $data = $this->buildDigest($user);

            if ($this->isEmptyDigest($data)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  would send → {$user->email}: ".count($data['deadlines']).' deadline(s), '
                    .count($data['next_steps'])." step(s), {$data['stalled_applications']} stalled app(s)");
                $sent++;

                continue;
            }

            Mail::to($user->email)->queue(new WeeklyDigestMail($user, $data));
            $user->forceFill(['weekly_digest_sent_at' => now()])->save();
            $sent++;
        }

        $prefix = $dryRun ? '[dry run] ' : '';
        $this->info("{$prefix}Sent {$sent} digest(s); skipped {$skipped} with nothing to say.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDigest(User $user): array
    {
        $horizon = now()->addDays(21);

        // Deadlines (curated + tracked scholarships) within 3 weeks.
        $deadlines = Deadline::relevantTo($user, upcomingOnly: true)
            ->filter(fn (Deadline $d) => $d->due_at->lte($horizon))
            ->map(fn (Deadline $d) => [
                'title' => $d->title,
                'date' => $d->due_at->format('j M Y'),
                'in' => 'in '.max(0, $d->daysUntil()).'d',
                'scope' => $d->scopeName(),
                'sort' => $d->due_at->timestamp,
            ]);

        $scholarshipDeadlines = ScholarshipTracker::query()
            ->where('user_id', $user->id)
            ->whereNotNull('deadline_at')
            ->whereBetween('deadline_at', [now()->startOfDay(), $horizon])
            ->get()
            ->map(fn (ScholarshipTracker $s) => [
                'title' => $s->label.' (scholarship)',
                'date' => $s->deadline_at->format('j M Y'),
                'in' => 'in '.max(0, (int) now()->startOfDay()->diffInDays($s->deadline_at, false)).'d',
                'scope' => null,
                'sort' => $s->deadline_at->timestamp,
            ]);

        $allDeadlines = $deadlines->concat($scholarshipDeadlines)->sortBy('sort')->take(6)->values()->all();

        // Next checklist steps.
        $items = $user->shortlistItems()->with('degreeProgram:id,admission_type,language')->get();
        $programs = $items->pluck('degreeProgram')->filter();

        $tokens = JourneyTemplate::activeTokens(
            isEuCitizen: (bool) $user->is_eu_citizen,
            wantsScholarship: (bool) $user->scholarship_interest,
            hasRestrictedProgram: $programs->contains('admission_type', 'restricted'),
            hasItalianTaughtProgram: $programs->contains('language', 'Italian'),
        );
        $steps = JourneyTemplate::stepsForTokens($tokens);
        $doneKeys = $user->journeyProgress()
            ->where('state', JourneyProgress::STATE_DONE)
            ->pluck('step_key')
            ->flip();

        $undone = collect($steps)->reject(fn ($s) => isset($doneKeys[$s['key']]));
        $checklistPercent = count($steps) > 0
            ? (int) round((count($steps) - $undone->count()) / count($steps) * 100)
            : 0;

        // Stalled applications: preparing/submitted but checklist incomplete.
        $stalled = $items
            ->whereIn('status', ['applying', 'submitted'])
            ->filter(fn (ShortlistItem $i) => $i->applicationChecklist((bool) $user->is_eu_citizen)['percent'] < 100)
            ->count();

        return [
            'deadlines' => $allDeadlines,
            'next_steps' => $undone->take(3)->pluck('title')->all(),
            'checklist_percent' => $checklistPercent,
            'stalled_applications' => $stalled,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isEmptyDigest(array $data): bool
    {
        return empty($data['deadlines'])
            && empty($data['next_steps'])
            && ($data['stalled_applications'] ?? 0) === 0;
    }
}
