<?php

namespace App\Console\Commands;

use App\Filament\Pages\MyApplications;
use App\Filament\Pages\MyDeadlines;
use App\Models\Deadline;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Daily in-app (bell) digest of what moved on each student's shortlist:
 * saved programs whose admission details an editor changed, and new
 * deadlines that became relevant to their list. "Since when" is one global
 * cache marker — good enough for a once-a-day job and needs no schema.
 *
 * Filament database notifications are already enabled on the panel (see
 * AdminPanelProvider), so these show in the topbar bell with no extra
 * wiring.
 */
class NotifyShortlistChanges extends Command
{
    protected $signature = 'unihup:notify-shortlist-changes {--dry-run} {--since= : ISO datetime to diff from, overriding the stored marker}';

    protected $description = 'Send each student a bell notification summarising changes to their shortlisted programs';

    private const MARKER = 'shortlist-changes:last-run';

    public function handle(): int
    {
        $since = $this->option('since')
            ? now()->parse($this->option('since'))
            : Cache::get(self::MARKER, now()->subDay());

        $dryRun = (bool) $this->option('dry-run');
        $runAt = now();
        $notified = 0;

        $users = User::query()
            ->whereNotNull('email_verified_at')
            ->whereHas('shortlistItems')
            ->with('shortlistItems.degreeProgram.university')
            ->get();

        foreach ($users as $user) {
            $programs = $user->shortlistItems->pluck('degreeProgram')->filter();

            $changedPrograms = $programs
                ->filter(fn ($p) => $p->updated_at !== null && $p->updated_at->gt($since))
                ->values();

            $newDeadlines = Deadline::relevantTo($user, upcomingOnly: true, shortlistItems: $user->shortlistItems)
                ->filter(fn (Deadline $d) => $d->created_at !== null && $d->created_at->gt($since))
                ->values();

            if ($changedPrograms->isEmpty() && $newDeadlines->isEmpty()) {
                continue;
            }

            $notified++;

            if ($dryRun) {
                $this->line("would notify {$user->email}: {$changedPrograms->count()} changed, {$newDeadlines->count()} new deadlines");

                continue;
            }

            $this->notify($user, $changedPrograms, $newDeadlines);
        }

        if (! $dryRun) {
            Cache::forever(self::MARKER, $runAt);
        }

        $this->info("{$notified} student(s) notified.");

        return self::SUCCESS;
    }

    private function notify(User $user, $changedPrograms, $newDeadlines): void
    {
        $lines = [];

        if ($changedPrograms->isNotEmpty()) {
            $names = $changedPrograms->take(3)
                ->map(fn ($p) => $p->university?->display_name ?? $p->name)
                ->implode(', ');
            $more = $changedPrograms->count() > 3 ? ' and '.($changedPrograms->count() - 3).' more' : '';
            $lines[] = "Admission details updated for {$names}{$more}.";
        }

        if ($newDeadlines->isNotEmpty()) {
            $lines[] = $newDeadlines->count().' new '.str('deadline')->plural($newDeadlines->count())
                .' on your shortlist — check the dates.';
        }

        Notification::make()
            ->title('Your shortlist has updates')
            ->body(implode(' ', $lines))
            ->icon('heroicon-o-bell-alert')
            ->actions([
                Action::make('applications')->label('My Applications')->url(MyApplications::getUrl(), shouldOpenInNewTab: false),
                Action::make('deadlines')->label('My Deadlines')->url(MyDeadlines::getUrl(), shouldOpenInNewTab: false)
                    ->visible($newDeadlines->isNotEmpty()),
            ])
            ->sendToDatabase($user);
    }
}
