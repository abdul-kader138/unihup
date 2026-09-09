<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            // What this deadline attaches to — see App\Models\Deadline::SCOPE_TYPES.
            // 'global' / 'admission_test' carry no scope_id; the rest reference a
            // universities / degree_programs / regional_scholarships row.
            $table->string('scope_type')->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            // App\Models\Deadline::CATEGORIES.
            $table->string('category')->default('other');

            $table->timestamp('due_at');
            // How exact the date is: 'day' (precise), 'month' (only the month is
            // known), 'window' (closes around then). Reminders only fire for 'day'.
            $table->string('due_precision')->default('day');
            // e.g. "2026/2027" — the intake this belongs to.
            $table->string('cycle_label')->nullable();

            $table->string('url')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
