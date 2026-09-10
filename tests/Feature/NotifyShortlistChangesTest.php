<?php

namespace Tests\Feature;

use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifyShortlistChangesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function studentWithShortlist(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('panel_user');

        $subject = Subject::create(['name' => 'CS', 'slug' => 'cs']);
        $university = University::create(['name' => 'Uni', 'slug' => 'uni', 'city' => 'Rome']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ]);
        ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching']);

        return [$user, $program];
    }

    public function test_a_changed_program_produces_a_database_notification(): void
    {
        [$user, $program] = $this->studentWithShortlist();

        $this->travel(2)->days();
        $program->update(['tuition_note' => 'Fees revised for 2026/27']);

        $this->artisan('unihup:notify-shortlist-changes', ['--since' => now()->subDay()->toIso8601String()])
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
    }

    public function test_nothing_is_sent_when_the_shortlist_is_quiet(): void
    {
        [$user] = $this->studentWithShortlist();

        $this->artisan('unihup:notify-shortlist-changes', ['--since' => now()->addMinute()->toIso8601String()])
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
    }

    public function test_a_new_relevant_deadline_is_announced(): void
    {
        [$user] = $this->studentWithShortlist();

        $this->travel(2)->days();
        Deadline::create([
            'scope_type' => Deadline::SCOPE_GLOBAL, 'title' => 'Universitaly pre-enrolment',
            'category' => 'pre_enrolment', 'due_at' => now()->addMonths(2), 'due_precision' => 'day', 'is_active' => true,
        ]);

        $this->artisan('unihup:notify-shortlist-changes', ['--since' => now()->subDay()->toIso8601String()])
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
    }
}
