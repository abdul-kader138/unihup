<?php

namespace Tests\Feature;

use App\Filament\Pages\MyApplications;
use App\Models\DegreeProgram;
use App\Models\ShortlistItem;
use App\Models\StudentDocument;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    private function setup2(): array
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $subject = Subject::firstOrCreate(['slug' => 'eng'], ['name' => 'Engineering']);
        $slug = 'u-'.Str::random(8);
        $university = University::create(['name' => 'U '.$slug, 'slug' => $slug, 'city' => 'Milan']);
        $program = DegreeProgram::create([
            'university_id' => $university->id, 'subject_id' => $subject->id, 'degree_level' => 'master',
            'name' => 'MSc', 'language' => 'English', 'duration_years' => 2, 'admission_type' => 'open',
        ]);
        $item = ShortlistItem::create(['user_id' => $user->id, 'degree_program_id' => $program->id, 'status' => 'applying']);

        $passport = StudentDocument::create(['user_id' => $user->id, 'type' => 'passport', 'status' => 'ready']);
        $transcript = StudentDocument::create(['user_id' => $user->id, 'type' => 'transcript', 'status' => 'ready']);

        return [$user, $item, $passport, $transcript];
    }

    /**
     * Drive the linkDocuments modal the way real Livewire array binding does:
     * whole-array replacement, not the per-leaf merge callTableAction() applies.
     *
     * @param  array<int>  $attached
     * @param  array<int>  $submitted
     */
    private function linkDocuments(User $user, ShortlistItem $item, array $attached, array $submitted): void
    {
        Livewire::actingAs($user)
            ->test(MyApplications::class)
            ->mountTableAction('linkDocuments', $item)
            ->set('mountedTableActionsData.0.attached', $attached)
            ->set('mountedTableActionsData.0.submitted', $submitted)
            ->callMountedTableAction();
    }

    public function test_linking_and_marking_submitted_syncs_the_pivot(): void
    {
        [$user, $item, $passport, $transcript] = $this->setup2();

        $this->linkDocuments($user, $item, [$passport->id, $transcript->id], [$passport->id]);

        $this->assertDatabaseHas('application_documents', [
            'shortlist_item_id' => $item->id, 'student_document_id' => $passport->id, 'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('application_documents', [
            'shortlist_item_id' => $item->id, 'student_document_id' => $transcript->id, 'status' => 'attached',
        ]);
        $this->assertCount(2, $item->fresh()->documents);
    }

    public function test_unchecking_a_document_detaches_it(): void
    {
        [$user, $item, $passport, $transcript] = $this->setup2();

        $this->linkDocuments($user, $item, [$passport->id, $transcript->id], []);
        $this->assertCount(2, $item->fresh()->documents);

        $this->linkDocuments($user, $item, [$passport->id], []);

        $this->assertDatabaseMissing('application_documents', [
            'shortlist_item_id' => $item->id, 'student_document_id' => $transcript->id,
        ]);
        $this->assertCount(1, $item->fresh()->documents);
    }

    public function test_a_document_belonging_to_another_user_cannot_be_linked(): void
    {
        [$user, $item] = $this->setup2();
        $other = User::factory()->create();
        $foreign = StudentDocument::create(['user_id' => $other->id, 'type' => 'passport', 'status' => 'ready']);

        $this->linkDocuments($user, $item, [$foreign->id], []);

        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_links_cascade_when_the_shortlist_item_is_removed(): void
    {
        [$user, $item, $passport] = $this->setup2();
        $item->documents()->sync([$passport->id => ['status' => 'attached']]);

        $item->delete();

        $this->assertDatabaseCount('application_documents', 0);
    }
}
