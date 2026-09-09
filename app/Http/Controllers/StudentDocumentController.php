<?php

namespace App\Http\Controllers;

use App\Models\StudentDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a file from a student's private document vault back to that same
 * student. Files live off the public disk (storage/app/private/...), so this
 * auth-gated route is the only way to retrieve one — mirrors
 * App\Http\Controllers\WhatsAppMediaController.
 */
class StudentDocumentController extends Controller
{
    public function __invoke(StudentDocument $document): StreamedResponse
    {
        abort_unless($document->user_id === auth()->id(), 403);
        abort_if($document->file_path === null, 404);

        $disk = Storage::disk(StudentDocument::DISK);
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path),
        );
    }
}
