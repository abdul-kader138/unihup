<?php

namespace App\Console\Commands;

use App\Jobs\SendWebPushNotification;
use App\Jobs\SendWhatsAppMessageJob;
use App\Mail\DeadlineReminderMail;
use App\Models\Deadline;
use App\Models\DeadlineReminderLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Emails (and, where configured, WhatsApps) each student the deadlines on
 * their shortlist that are exactly 14 / 3 / 1 days away. Idempotent via
 * deadline_reminder_log — safe to run more than once a day. Scheduled daily
 * in routes/console.php.
 */
class SendDeadlineReminders extends Command
{
    protected $signature = 'unihup:send-deadline-reminders {--dry-run : List what would be sent without sending}';

    protected $description = 'Send 14/3/1-day reminders for the deadlines on each student\'s shortlist';

    public function handle(WhatsAppClient $whatsapp): int
    {
        if (! (bool) Setting::get('deadline_reminders_enabled', true)) {
            $this->info('Deadline reminders are disabled in System Settings — nothing sent.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $waConfigured = $whatsapp->configured();
        $mails = 0;
        $waMessages = 0;
        $pushes = 0;

        $users = User::query()
            ->where('deadline_reminders_opt_out', false)
            ->whereNotNull('email_verified_at')
            ->whereHas('shortlistItems')
            ->get();

        foreach ($users as $user) {
            $relevant = Deadline::relevantTo($user, upcomingOnly: true);

            if ($relevant->isEmpty()) {
                continue;
            }

            foreach (Deadline::REMINDER_OFFSETS as $offset) {
                $targetDate = now()->addDays($offset)->startOfDay();

                $due = $relevant->filter(fn (Deadline $d) => $d->due_precision === 'day'
                    && $d->due_at->copy()->startOfDay()->equalTo($targetDate));

                if ($due->isEmpty()) {
                    continue;
                }

                $mails += $this->sendMail($user, $due, $offset, $dryRun) ? 1 : 0;

                if ($waConfigured && $user->whatsapp_number && $user->whatsapp_opt_in) {
                    $waMessages += $this->sendWhatsApp($user, $due, $offset, $dryRun) ? 1 : 0;
                }

                if ($user->pushSubscriptions()->exists()) {
                    $pushes += $this->sendWebPush($user, $due, $offset, $dryRun) ? 1 : 0;
                }
            }
        }

        $prefix = $dryRun ? '[dry run] ' : '';
        $this->info("{$prefix}Sent {$mails} reminder email(s), {$waMessages} WhatsApp message(s) and {$pushes} push notification(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Deadline>  $due
     */
    private function sendMail(User $user, Collection $due, int $offset, bool $dryRun): bool
    {
        $unsent = $this->unlogged($user, $due, $offset, DeadlineReminderLog::CHANNEL_MAIL);

        if ($unsent->isEmpty()) {
            return false;
        }

        if ($dryRun) {
            $this->line("  mail → {$user->email}: {$unsent->pluck('title')->implode(', ')} (T-{$offset})");

            return true;
        }

        Mail::to($user->email)->queue(new DeadlineReminderMail($user, $unsent->values(), $offset));
        $this->log($user, $unsent, $offset, DeadlineReminderLog::CHANNEL_MAIL);

        return true;
    }

    /**
     * @param  Collection<int, Deadline>  $due
     */
    private function sendWhatsApp(User $user, Collection $due, int $offset, bool $dryRun): bool
    {
        $unsent = $this->unlogged($user, $due, $offset, DeadlineReminderLog::CHANNEL_WHATSAPP);

        if ($unsent->isEmpty()) {
            return false;
        }

        if ($dryRun) {
            $this->line("  whatsapp → {$user->whatsapp_number}: {$unsent->count()} deadline(s) (T-{$offset})");

            return true;
        }

        try {
            $template = (string) Setting::get('deadline_reminder_whatsapp_template', 'deadline_reminder');
            $language = (string) Setting::get('deadline_reminder_whatsapp_language', 'en');
            $first = $unsent->first();
            $summary = $unsent->count() === 1
                ? $first->title
                : "{$unsent->count()} deadlines (next: {$first->title})";

            $conversation = $this->conversationFor($user);

            $message = $conversation->messages()->create([
                'direction' => WhatsAppMessage::DIRECTION_OUT,
                'type' => 'template',
                'body' => "[template: {$template}] {$summary} — ".now()->addDays($offset)->format('j M Y'),
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ]);

            SendWhatsAppMessageJob::dispatch($message, $template, $language);
            $this->log($user, $unsent, $offset, DeadlineReminderLog::CHANNEL_WHATSAPP);

            return true;
        } catch (\Throwable $e) {
            $this->warn("  whatsapp send skipped for user {$user->id}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @param  Collection<int, Deadline>  $due
     */
    private function sendWebPush(User $user, Collection $due, int $offset, bool $dryRun): bool
    {
        $unsent = $this->unlogged($user, $due, $offset, DeadlineReminderLog::CHANNEL_WEBPUSH);

        if ($unsent->isEmpty()) {
            return false;
        }

        if ($dryRun) {
            $this->line("  push → user {$user->id}: {$unsent->count()} deadline(s) (T-{$offset})");

            return true;
        }

        $first = $unsent->first();
        $title = $offset === 1 ? 'Deadline tomorrow' : "Deadline in {$offset} days";
        $body = $unsent->count() === 1
            ? $first->title
            : "{$first->title} + ".($unsent->count() - 1).' more';

        SendWebPushNotification::dispatch($user, [
            'title' => $title,
            'body' => $body,
            'url' => route('filament.admin.pages.my-deadlines'),
            'tag' => "deadline-{$offset}",
        ]);

        $this->log($user, $unsent, $offset, DeadlineReminderLog::CHANNEL_WEBPUSH);

        return true;
    }

    /**
     * @param  Collection<int, Deadline>  $due
     * @return Collection<int, Deadline>
     */
    private function unlogged(User $user, Collection $due, int $offset, string $channel): Collection
    {
        $alreadySent = DeadlineReminderLog::query()
            ->where('user_id', $user->id)
            ->where('offset_days', $offset)
            ->where('channel', $channel)
            ->whereIn('deadline_id', $due->pluck('id'))
            ->pluck('deadline_id')
            ->flip();

        return $due->reject(fn (Deadline $d) => isset($alreadySent[$d->id]))->values();
    }

    /**
     * @param  Collection<int, Deadline>  $deadlines
     */
    private function log(User $user, Collection $deadlines, int $offset, string $channel): void
    {
        $now = now();

        foreach ($deadlines as $deadline) {
            DeadlineReminderLog::firstOrCreate([
                'user_id' => $user->id,
                'deadline_id' => $deadline->id,
                'offset_days' => $offset,
                'channel' => $channel,
            ], ['sent_at' => $now]);
        }
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
