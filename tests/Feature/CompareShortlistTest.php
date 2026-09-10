<?php

namespace Tests\Feature;

use App\Filament\Pages\CompareShortlist;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompareShortlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        return $user;
    }

    /**
     * @return array<int, DegreeProgram>
     */
    private function shortlist(User $user, int $count): array
    {
        $subject = Subject::create(['name' => 'Computer Science', 'slug' => 'computer-science']);

        $programs = [];
        for ($i = 1; $i <= $count; $i++) {
            $university = University::create([
                'name' => "University $i", 'slug' => "university-$i", 'city' => 'Rome',
            ]);
            $program = DegreeProgram::create([
                'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'bachelor',
                'name' => "BSc $i", 'language' => 'English', 'duration_years' => 3, 'admission_type' => 'open',
            ]);
            ShortlistItem::create([
                'user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'researching',
            ]);
            $programs[] = $program;
        }

        return $programs;
    }

    public function test_it_preselects_up_to_the_column_cap(): void
    {
        $user = $this->student();
        $this->shortlist($user, 10);

        Livewire::actingAs($user)
            ->test(CompareShortlist::class)
            ->assertCount('selected', CompareShortlist::MAX_COLUMNS);
    }

    public function test_a_student_can_toggle_a_program_into_and_out_of_the_comparison(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 3);
        $extra = $programs[2]->id;

        $component = Livewire::actingAs($user)->test(CompareShortlist::class);
        $component->set('selected', [$programs[0]->id]);

        $component->call('toggle', $extra)
            ->assertSet('selected', fn (array $ids) => in_array($extra, $ids, true))
            ->call('toggle', $extra)
            ->assertSet('selected', fn (array $ids) => ! in_array($extra, $ids, true));
    }

    public function test_toggling_a_program_in_is_ignored_once_at_capacity(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, CompareShortlist::MAX_COLUMNS + 1);
        $overflow = end($programs)->id;

        $full = array_slice(array_map(fn ($p) => $p->id, $programs), 0, CompareShortlist::MAX_COLUMNS);

        Livewire::actingAs($user)
            ->test(CompareShortlist::class)
            ->set('selected', $full)
            ->call('toggle', $overflow)
            ->assertSet('selected', fn (array $ids) => ! in_array($overflow, $ids, true))
            ->assertCount('selected', CompareShortlist::MAX_COLUMNS);
    }

    public function test_remove_drops_a_column_without_touching_the_shortlist(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 3);
        $drop = $programs[0]->id;

        Livewire::actingAs($user)
            ->test(CompareShortlist::class)
            ->call('remove', $drop)
            ->assertSet('selected', fn (array $ids) => ! in_array($drop, $ids, true));

        $this->assertDatabaseHas('shortlist_items', [
            'user_id' => $user->id,
            'degree_program_id' => $drop,
        ]);
    }

    public function test_stale_ids_in_the_url_are_dropped_on_mount(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 2);
        $valid = $programs[0]->id;

        Livewire::withQueryParams(['programs' => [$valid, 999999]])
            ->actingAs($user)
            ->test(CompareShortlist::class)
            ->assertSet('selected', [$valid]);
    }

    public function test_the_grid_flags_the_cheapest_and_best_ranked_columns(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 3);

        $programs[0]->update(['tuition_min' => 4000]);
        $programs[1]->update(['tuition_min' => 1000]);
        $programs[2]->update(['tuition_min' => 3000]);

        // Two ranked universities so there's a genuine "best" to pick — the
        // denormalised columns aren't fillable, so write them directly.
        $programs[0]->university->forceFill(['latest_ranking_position' => 7])->save();
        $programs[1]->university->forceFill(['latest_ranking_position' => 1])->save();

        $grid = Livewire::actingAs($user)
            ->test(CompareShortlist::class)
            ->set('selected', collect($programs)->pluck('id')->all())
            ->instance()
            ->getComparison();

        $rows = collect($grid['groups'])->flatMap(fn ($g) => $g['rows'])->keyBy('label');
        $winner = array_search($programs[1]->id, array_column($grid['programs'], 'id'), true);
        $loser = array_search($programs[0]->id, array_column($grid['programs'], 'id'), true);

        $this->assertTrue($rows['Tuition']['values'][$winner]['best']);
        $this->assertFalse($rows['Tuition']['values'][$loser]['best']);
        $this->assertTrue($rows['CENSIS ranking']['values'][$winner]['best']);
    }

    public function test_only_differences_hides_rows_where_every_column_agrees(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 2); // identical language / level / city

        $component = Livewire::actingAs($user)
            ->test(CompareShortlist::class)
            ->set('selected', collect($programs)->pluck('id')->all());

        $rowCount = fn (array $grid) => collect($grid['groups'])->sum(fn ($g) => count($g['rows']));

        $full = $rowCount($component->instance()->getComparison());
        $component->call('toggleOnlyDifferences');
        $filteredGrid = $component->instance()->getComparison();

        $this->assertLessThan($full, $rowCount($filteredGrid));
        $this->assertGreaterThan(0, $filteredGrid['hidden_rows']);
    }

    public function test_pdf_export_is_scoped_to_the_owner_shortlist(): void
    {
        $user = $this->student();
        $programs = $this->shortlist($user, 2);

        $ok = $this->actingAs($user)->get(route('compare.pdf', ['programs' => collect($programs)->pluck('id')->all()]));
        $ok->assertOk();
        $this->assertSame('application/pdf', $ok->headers->get('content-type'));

        $this->actingAs($user)->get(route('compare.pdf', ['programs' => [999999]]))->assertNotFound();
    }
}
