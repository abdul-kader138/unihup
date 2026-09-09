<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // One of App\Support\DocumentChecklist::TYPES keys (passport, dov,
            // admission_letter, visa_d, ...). 'other' is free-form via `label`.
            $table->string('type');
            $table->string('label')->nullable();
            // One of App\Models\StudentDocument::STATUSES.
            $table->string('status')->default('needed');
            // Stored on the private 'local' disk under
            // student-documents/{user_id}/ — never web-served directly, only
            // through App\Http\Controllers\StudentDocumentController.
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            // Passport / visa / health-insurance expiry — surfaced on the vault
            // and (later) drives renewal reminders.
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
