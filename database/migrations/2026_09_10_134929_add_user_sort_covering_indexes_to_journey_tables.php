<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Both tables are read as "this student's rows, in a particular order" on
 * every visit to the pages that own them:
 *
 *   shortlist_items      WHERE user_id = ? ORDER BY sort_order   (MyApplications, CompareShortlist)
 *   scholarship_tracker  WHERE user_id = ? ORDER BY deadline_at  (MyScholarships)
 *
 * The existing unique(user_id, ...) constraints satisfy the WHERE via their
 * left-most prefix but leave the ORDER BY as a filesort. These composite
 * indexes let the sort come straight off the index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlist_items', function (Blueprint $table) {
            $table->index(['user_id', 'sort_order'], 'shortlist_items_user_sort_idx');
        });

        Schema::table('scholarship_tracker', function (Blueprint $table) {
            $table->index(['user_id', 'deadline_at'], 'scholarship_tracker_user_deadline_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shortlist_items', function (Blueprint $table) {
            $table->dropIndex('shortlist_items_user_sort_idx');
        });

        Schema::table('scholarship_tracker', function (Blueprint $table) {
            $table->dropIndex('scholarship_tracker_user_deadline_idx');
        });
    }
};
