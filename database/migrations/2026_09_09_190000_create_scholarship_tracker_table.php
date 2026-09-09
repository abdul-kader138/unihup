<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_tracker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 'regional' -> ref is a regional_scholarships id; 'national' -> ref
            // is a key from App\Support\NationalScholarships::LIST.
            $table->string('kind');
            $table->string('ref');
            // Denormalised name — keeps the row meaningful even if a regional
            // scholarship row is later removed.
            $table->string('label');
            $table->string('status')->default('interested');
            $table->date('deadline_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kind', 'ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_tracker');
    }
};
