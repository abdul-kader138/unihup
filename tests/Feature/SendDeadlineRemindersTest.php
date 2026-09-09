<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotification;
use App\Mail\DeadlineReminderMail;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\PushSubscription;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendDeadlineRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
    }

    private function studentWithShortlist(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        $user->assignRole('panel_user');

        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ]);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        return $user;
    }

    public function test_it_emails_a_reminder_for_a_deadline_three_days_out_and_logs_it(): void
    {
        $user = $this->studentWithShortlist();
        $deadline = Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Application closes', 'category' => 'application',
            'due_at' => now()->addDays(3)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Mail::assertQueued(DeadlineReminderMail::class, fn (DeadlineReminderMail $mail) => $mail->hasTo($user->email) && $mail->offsetDays === 3);

        $this->assertDatabaseHas('deadline_reminder_log', [
            'user_id' => $user->id,
            'deadline_id' => $deadline->id,
            'offset_days' => 3,
            'channel' => 'mail',
        ]);
    }

    public function test_a_second_run_does_not_send_again(): void
    {
        $this->studentWithShortlist();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'X', 'category' => 'application',
            'due_at' => now()->addDays(1)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();
        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Mail::assertQueuedCount(1);
    }

    public function test_opted_out_students_get_nothing(): void
    {
        $this->studentWithShortlist(['deadline_reminders_opt_out' => true]);
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'X', 'category' => 'application',
            'due_at' => now()->addDays(3)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_non_day_precision_deadlines_do_not_trigger_reminders(): void
    {
        $this->studentWithShortlist();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Fuzzy window', 'category' => 'application',
            'due_at' => now()->addDays(3)->setTime(9, 0), 'due_precision' => 'window', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_it_pushes_to_a_subscribed_student_once_and_logs_it(): void
    {
        Bus::fake();

        $user = $this->studentWithShortlist();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example/abc',
            'endpoint_hash' => PushSubscription::hashFor('https://push.example/abc'),
            'public_key' => 'p', 'auth_token' => 'a',
        ]);

        $deadline = Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Application closes', 'category' => 'application',
            'due_at' => now()->addDays(3)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();
        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Bus::assertDispatchedTimes(SendWebPushNotification::class, 1);
        $this->assertDatabaseHas('deadline_reminder_log', [
            'user_id' => $user->id, 'deadline_id' => $deadline->id, 'offset_days' => 3, 'channel' => 'webpush',
        ]);
    }

    public function test_a_student_without_a_push_subscription_triggers_no_push_job(): void
    {
        Bus::fake();

        $this->studentWithShortlist();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'X', 'category' => 'application',
            'due_at' => now()->addDays(1)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Bus::assertNotDispatched(SendWebPushNotification::class);
    }

    public function test_a_deadline_outside_the_offset_windows_is_not_sent(): void
    {
        $this->studentWithShortlist();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Far off', 'category' => 'application',
            'due_at' => now()->addDays(9)->setTime(9, 0), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:send-deadline-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
