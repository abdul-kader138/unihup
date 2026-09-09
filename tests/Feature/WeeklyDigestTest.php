<?php

namespace Tests\Feature;

use App\Mail\WeeklyDigestMail;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use App\Support\JourneyTemplate;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
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

    private function globalDeadline(int $daysAhead): void
    {
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Something due', 'category' => 'application',
            'due_at' => now()->addDays($daysAhead), 'due_precision' => 'day', 'is_active' => true,
        ]);
    }

    public function test_it_sends_a_digest_and_stamps_the_send_time(): void
    {
        $user = $this->studentWithShortlist();
        $this->globalDeadline(10);

        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();

        Mail::assertQueued(WeeklyDigestMail::class, fn ($m) => $m->hasTo($user->email));
        $this->assertNotNull($user->fresh()->weekly_digest_sent_at);
    }

    public function test_it_skips_a_student_with_nothing_to_say(): void
    {
        // Shortlist but no deadlines, EU citizen so a short "always" checklist,
        // mark every applicable step done → empty digest.
        $user = $this->studentWithShortlist(['is_eu_citizen' => true]);
        foreach (JourneyTemplate::stepsForTokens(['always']) as $s) {
            $user->journeyProgress()->create(['step_key' => $s['key'], 'state' => 'done', 'completed_at' => now()]);
        }

        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_it_only_sends_once_per_week(): void
    {
        $this->studentWithShortlist();
        $this->globalDeadline(5);

        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();
        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();

        Mail::assertQueuedCount(1);
    }

    public function test_force_flag_bypasses_the_weekly_guard(): void
    {
        $this->studentWithShortlist();
        $this->globalDeadline(5);

        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();
        $this->artisan('unihup:send-weekly-digest --force')->assertSuccessful();

        Mail::assertQueuedCount(2);
    }

    public function test_opted_out_students_get_nothing(): void
    {
        $this->studentWithShortlist(['deadline_reminders_opt_out' => true]);
        $this->globalDeadline(5);

        $this->artisan('unihup:send-weekly-digest')->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
