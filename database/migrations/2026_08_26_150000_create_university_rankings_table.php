<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            // e.g. "2025/2026" — CENSIS publishes a new edition annually.
            $table->string('edition');
            // One of App\Models\UniversityRanking::CATEGORIES — CENSIS ranks
            // state vs. private universities separately, and each in size
            // bands, so position 1 means "1st among mega state universities",
            // not 1st overall.
            $table->string('category');
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('score_services')->nullable();
            $table->unsignedSmallInteger('score_scholarships')->nullable();
            $table->unsignedSmallInteger('score_facilities')->nullable();
            $table->unsignedSmallInteger('score_communication_digital')->nullable();
            $table->unsignedSmallInteger('score_internationalization')->nullable();
            // Null for non-state universities — CENSIS's own methodology
            // excludes employability from that table (see RankingSeeder).
            $table->unsignedSmallInteger('score_employability')->nullable();
            $table->decimal('overall_score', 4, 1);
            $table->string('source_url')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['university_id', 'edition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_rankings');
    }
};
