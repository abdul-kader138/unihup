<?php

namespace App\Console\Commands;

use App\Jobs\SendWebPushNotification;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Setting;
use App\Models\ShortlistItem;
use App\Models\StalledProgressNudgeLog;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Console\Command;

/**
 * Proactive WhatsApp/push nudge for shortlist items that have gone quiet —
 * still active (not admitted/rejected/withdrawn/enrolled), created 10+ days
 * ago, and no checklist step completed in the last 10 days. Idempotent via
 * stalled_progress_nudge_logs (14-day cooldown per item). Scheduled daily in
 * routes/console.php, offset from the 07:00 deadline-reminder run.
 */
class NotifyStalledProgress extends Command
{
    protected $signature = 'unihup:notify-stalled-progress {--dry-run : List what would be sent without sending}';

    protected $description = 'Nudge students whose shortlisted applications have had no checklist progress in 10+ days';

    /** Statuses still considered "in progress" — everything else is terminal. */
    private const ACTIVE_STATUSES = ['researching', 'applying', 'submitted', 'waitlisted'];

    private const STALE_AFTER_DAYS = 10;

    private const COOLDOWN_DAYS = 14;

    public function handle(WhatsAppClient $whatsapp): int
    {
        if (! (bool) Setting::get('stalled_progress_nudges_enabled', true)) {
            $this->info('Stalled progress nudges are disabled in System Settings — nothing sent.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $waConfigured = $whatsapp->configured();
        $cutoff = now()->subDays(self::STALE_AFTER_DAYS);
        $cooldownCutoff = now()->subDays(self::COOLDOWN_DAYS);
        $waMessages = 0;
        $pushes = 0;

        $stalledItems = ShortlistItem::query()
            ->with(['user', 'degreeProgram.university'])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('applicationProgress', fn ($q) => $q->where('done', true)->where('completed_at', '>=', $cutoff))
            ->whereDoesntHave('stalledProgressNudgeLog', fn ($q) => $q->where('sent_at', '>', $cooldownCutoff))
            ->get()
            ->filter(fn (ShortlistItem $item) => $item->user !== null
                && ! $item->user->deadline_reminders_opt_out
                && $item->user->email_verified_at !== null);

        foreach ($stalledItems as $item) {
            $user = $item->user;
            $programName = $item->degreeProgram?->university?->display_name ?? $item->degreeProgram?->name ?? 'a saved program';

            $sentAny = false;

            if ($waConfigured && $user->whatsapp_number && $user->whatsapp_opt_in) {
                $waMessages += $this->sendWhatsApp($user, $item, $programName, $dryRun) ? 1 : 0;
                $sentAny = true;
            }

            if ($user->pushSubscriptions()->exists()) {
                $pushes += $this->sendWebPush($user, $item, $programName, $dryRun) ? 1 : 0;
                $sentAny = true;
            }

            if ($sentAny && ! $dryRun) {
                StalledProgressNudgeLog::updateOrCreate(
                    ['shortlist_item_id' => $item->id],
                    ['user_id' => $user->id, 'sent_at' => now()],
                );
            }
        }

        $prefix = $dryRun ? '[dry run] ' : '';
        $this->info("{$prefix}Sent {$waMessages} WhatsApp message(s) and {$pushes} push notification(s) for stalled applications.");

        return self::SUCCESS;
    }

    private function sendWhatsApp(User $user, ShortlistItem $item, string $programName, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line("  whatsapp → {$user->whatsapp_number}: nudge for {$programName}");

            return true;
        }

        try {
            $template = (string) Setting::get('stalled_progress_whatsapp_template', 'stalled_progress_nudge');
            $language = (string) Setting::get('stalled_progress_whatsapp_language', 'en');

            $conversation = $this->conversationFor($user);

            $message = $conversation->messages()->create([
                'direction' => WhatsAppMessage::DIRECTION_OUT,
                'type' => 'template',
                'body' => "[template: {$template}] Still working on {$programName}? Pick up where you left off.",
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ]);

            SendWhatsAppMessageJob::dispatch($message, $template, $language);

            return true;
        } catch (\Throwable $e) {
            $this->warn("  whatsapp send skipped for user {$user->id}: {$e->getMessage()}");

            return false;
        }
    }

    private function sendWebPush(User $user, ShortlistItem $item, string $programName, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line("  push → user {$user->id}: nudge for {$programName}");

            return true;
        }

        SendWebPushNotification::dispatch($user, [
            'title' => 'Pick up where you left off',
            'body' => "You haven't touched your {$programName} checklist in a while.",
            'url' => route('filament.admin.pages.my-applications'),
            'tag' => "stalled-{$item->id}",
        ]);

        return true;
    }

    private function conversationFor(User $user): WhatsAppConversation
    {
        $existing = WhatsAppConversation::where('user_id', $user->id)->first();

        if ($existing) {
            return $existing;
        }

        return WhatsAppConversation::create([
            'wa_contact_id' => WhatsAppClient::normalizeNumber($user->whatsapp_number),
            'wa_contact_name' => $user->name,
            'user_id' => $user->id,
            'status' => WhatsAppConversation::STATUS_PENDING,
        ]);
    }
}
