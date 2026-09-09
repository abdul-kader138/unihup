<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The Monday "your week on UniHup" digest — upcoming deadlines, the next
 * checklist steps, and any stalled applications. Sent by
 * App\Console\Commands\SendWeeklyDigest, which also builds $data and skips
 * students who have nothing worth an email.
 *
 * @property array<string, mixed> $data
 */
class WeeklyDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public User $user,
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your week on '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.weekly-digest');
    }
}
