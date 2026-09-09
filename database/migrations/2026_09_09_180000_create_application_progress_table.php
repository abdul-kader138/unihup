<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlist_item_id')->constrained()->cascadeOnDelete();
            // A key from App\Support\ApplicationSteps::STEPS. A missing row
            // means "pending".
            $table->string('step_key');
            $table->boolean('done')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['shortlist_item_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_progress');
    }
};
