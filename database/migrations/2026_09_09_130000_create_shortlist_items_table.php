<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('degree_program_id')->constrained()->cascadeOnDelete();
            // One of App\Models\ShortlistItem::STATUSES — where the student is
            // in the process for this specific program (researching -> enrolled).
            $table->string('status')->default('researching');
            $table->text('notes')->nullable();
            $table->timestamps();

            // A program is on a student's list at most once.
            $table->unique(['user_id', 'degree_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlist_items');
    }
};
