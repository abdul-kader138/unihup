<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A key from App\Support\JourneyTemplate::STEPS. Rows are created
            // lazily the first time a student ticks or skips a step — a missing
            // row means "pending".
            $table->string('step_key');
            $table->string('state')->default('pending'); // pending | done | skipped
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_progress');
    }
};
