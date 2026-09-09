<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlist_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_document_id')->constrained()->cascadeOnDelete();
            // 'attached' = earmarked for this application; 'submitted' = uploaded
            // to that university's portal.
            $table->string('status')->default('attached');
            $table->timestamps();

            $table->unique(['shortlist_item_id', 'student_document_id'], 'application_document_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
