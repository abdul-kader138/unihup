<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_reminder_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            // 14 / 3 / 1 — days before the deadline this reminder was for.
            $table->unsignedSmallInteger('offset_days');
            $table->string('channel'); // mail | whatsapp
            $table->timestamp('sent_at');

            // One reminder per user per deadline per offset per channel, ever.
            $table->unique(['user_id', 'deadline_id', 'offset_days', 'channel'], 'deadline_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_reminder_log');
    }
};
