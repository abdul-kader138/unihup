<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stalled_progress_nudge_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shortlist_item_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');

            // One row per shortlist item — sent_at is updated (not
            // re-inserted) on each re-fire, so a still-stalled item is only
            // nudged again after the cooldown window has passed.
            $table->unique('shortlist_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stalled_progress_nudge_logs');
    }
};
