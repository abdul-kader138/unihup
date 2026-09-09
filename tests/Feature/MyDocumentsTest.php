<?php

namespace Tests\Feature;

use App\Filament\Pages\MyDocuments;
use App\Models\StudentDocument;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MyDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Storage::fake('local');
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        return $user;
    }

    public function test_a_student_can_add_a_document_with_a_file(): void
    {
        $user = $this->student();

        $bytes = str_repeat('x', 4096);

        Livewire::actingAs($user)
            ->test(MyDocuments::class)
            ->callTableAction('create', data: [
                'type' => 'passport',
                'status' => 'ready',
                'file_path' => UploadedFile::fake()->createWithContent('passport.pdf', $bytes),
            ]);

        $document = StudentDocument::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('passport', $document->type);
        $this->assertSame('ready', $document->status);
        $this->assertNotNull($document->file_path);
        $this->assertStringStartsWith("student-documents/{$user->id}/", $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame(4096, $document->size_bytes);
        $this->assertSame('passport.pdf', $document->original_filename);
    }

    public function test_the_owner_can_download_their_file_but_another_user_cannot(): void
    {
        $owner = $this->student();
        $other = $this->student();

        Storage::disk('local')->put("student-documents/{$owner->id}/dov.pdf", 'PDF BYTES');

        $document = StudentDocument::create([
            'user_id' => $owner->id,
            'type' => 'dov',
            'status' => 'ready',
            'file_path' => "student-documents/{$owner->id}/dov.pdf",
            'original_filename' => 'dov.pdf',
        ]);

        $this->actingAs($other)
            ->get(route('student-documents.download', $document))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('student-documents.download', $document))
            ->assertOk()
            ->assertDownload('dov.pdf');
    }

    public function test_deleting_a_document_removes_the_file_from_disk(): void
    {
        $user = $this->student();
        Storage::disk('local')->put("student-documents/{$user->id}/x.pdf", 'bytes');

        $document = StudentDocument::create([
            'user_id' => $user->id,
            'type' => 'other',
            'status' => 'ready',
            'file_path' => "student-documents/{$user->id}/x.pdf",
        ]);

        $document->delete();

        Storage::disk('local')->assertMissing("student-documents/{$user->id}/x.pdf");
    }

    public function test_the_vault_only_lists_the_current_users_documents(): void
    {
        $mine = $this->student();
        $theirs = $this->student();

        $a = StudentDocument::create(['user_id' => $mine->id, 'type' => 'passport', 'status' => 'needed']);
        $b = StudentDocument::create(['user_id' => $theirs->id, 'type' => 'passport', 'status' => 'needed']);

        Livewire::actingAs($mine)
            ->test(MyDocuments::class)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }
}
