<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('degree_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            // 'bachelor' = laurea triennale / Honours; 'master' = laurea magistrale.
            $table->string('degree_level');
            $table->string('name');
            $table->string('language')->default('Italian');
            $table->unsignedTinyInteger('duration_years')->default(3);
            // Italy's "libero accesso" (open) vs "numero programmato" (restricted,
            // capped-seat admission test) — the single biggest fork in how
            // admission actually works, so it's a first-class column rather than
            // buried in admission_notes.
            $table->string('admission_type')->default('open');
            $table->text('admission_notes')->nullable();
            $table->text('tuition_note')->nullable();
            $table->text('application_window_note')->nullable();
            $table->string('official_admission_url')->nullable();
            // Defaults to studyinitaly.esteri.it or universitaly.it — see
            // App\Services\Universities\SeedDataImporter.
            $table->string('source_url')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('degree_programs');
    }
};
