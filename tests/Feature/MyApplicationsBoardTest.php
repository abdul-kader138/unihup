<?php

namespace Tests\Feature;

use App\Filament\Pages\MyApplicationsBoard;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyApplicationsBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(): User
    {
        $user = User::factory()->create(['study_profile_completed_at' => now()]);
        $user->assignRole('panel_user');

        return $user;
    }

    private function item(User $user, int $order, string $status = 'researching'): ShortlistItem
    {
        $subject = Subject::firstOrCreate(['slug' => 'cs'], ['name' => 'CS']);
        $university = University::create(['name' => 'Uni '.uniqid(), 'slug' => 'uni-'.uniqid(), 'city' => 'Rome']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
            'name' => 'BSc', 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
        ]);

        return ShortlistItem::create([
            'user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => $status, 'sort_order' => $order,
        ]);
    }

    public function test_the_board_groups_cards_by_status(): void
    {
        $user = $this->student();
        $this->item($user, 0, 'researching');
        $this->item($user, 1, 'submitted');

        $board = Livewire::actingAs($user)->test(MyApplicationsBoard::class)->instance()->getBoard();

        $byStatus = collect($board)->keyBy('status');
        $this->assertCount(1, $byStatus['researching']['cards']);
        $this->assertCount(1, $byStatus['submitted']['cards']);
        $this->assertCount(0, $byStatus['admitted']['cards']);
    }

    public function test_moving_a_card_changes_status_and_reorders_the_target_column(): void
    {
        $user = $this->student();
        $a = $this->item($user, 0, 'applying');
        $b = $this->item($user, 1, 'applying');
        $moving = $this->item($user, 2, 'researching');

        Livewire::actingAs($user)
            ->test(MyApplicationsBoard::class)
            ->call('moveCard', $moving->id, 'applying', $b->id); // insert before b

        $this->assertSame('applying', $moving->fresh()->status);

        // Order within "applying" is now a, moving, b.
        $order = ShortlistItem::whereIn('id', [$a->id, $moving->id, $b->id])
            ->orderBy('sort_order')->pluck('id')->all();
        $this->assertSame([$a->id, $moving->id, $b->id], $order);
    }

    public function test_moving_with_no_anchor_appends_to_the_column(): void
    {
        $user = $this->student();
        $a = $this->item($user, 0, 'admitted');
        $moving = $this->item($user, 1, 'researching');

        Livewire::actingAs($user)
            ->test(MyApplicationsBoard::class)
            ->call('moveCard', $moving->id, 'admitted', null);

        $order = ShortlistItem::where('status', 'admitted')->orderBy('sort_order')->pluck('id')->all();
        $this->assertSame([$a->id, $moving->id], $order);
    }

    public function test_an_unknown_status_is_ignored(): void
    {
        $user = $this->student();
        $item = $this->item($user, 0, 'researching');

        Livewire::actingAs($user)
            ->test(MyApplicationsBoard::class)
            ->call('moveCard', $item->id, 'not-a-status', null);

        $this->assertSame('researching', $item->fresh()->status);
    }
}
