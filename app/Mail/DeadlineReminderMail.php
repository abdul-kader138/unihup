<?php

namespace App\Mail;

use App\Models\Deadline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * One digest email covering every relevant deadline that is exactly
 * $offsetDays away for this student. Sent by
 * App\Console\Commands\SendDeadlineReminders. Uses whatever mailer
 * App\Providers\AppServiceProvider configured from the Settings table.
 *
 * @property Collection<int, Deadline> $deadlines
 */
class DeadlineReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $deadlines,
        public int $offsetDays,
    ) {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        $when = match ($this->offsetDays) {
            1 => 'tomorrow',
            default => "in {$this->offsetDays} days",
        };

        $count = $this->deadlines->count();
        $subject = $count === 1
            ? "Reminder: {$this->deadlines->first()->title} is due {$when}"
            : "{$count} application deadlines due {$when}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.deadline-reminder');
    }
}
