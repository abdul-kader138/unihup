<?php

use App\Models\University;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalised count of shortlist_items across a university's degree
 * programs, for the "X students shortlisted this university" social-proof
 * badge on University Profile. Kept fresh by an increment/decrement in
 * UniversityProfile::toggleShortlist() plus a daily
 * University::syncAllShortlistCounts() resync as a drift safety net — same
 * pattern as the existing latest_ranking_* columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->unsignedInteger('shortlist_items_count')->default(0)->after('latest_ranking_overall_score');
            $table->index('shortlist_items_count');
        });

        University::syncAllShortlistCounts();
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropIndex(['shortlist_items_count']);
            $table->dropColumn('shortlist_items_count');
        });
    }
};
