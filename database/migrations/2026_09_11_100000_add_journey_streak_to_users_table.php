<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Consecutive-day "opened My Journey" streak — a lightweight
            // gamification hook, bumped by User::recordJourneyActivity().
            $table->unsignedInteger('journey_streak_current')->default(0)->after('weekly_digest_sent_at');
            $table->unsignedInteger('journey_streak_longest')->default(0)->after('journey_streak_current');
            $table->date('journey_last_active_date')->nullable()->after('journey_streak_longest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['journey_streak_current', 'journey_streak_longest', 'journey_last_active_date']);
        });
    }
};
