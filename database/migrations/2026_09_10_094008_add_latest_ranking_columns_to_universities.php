<?php

use App\Models\University;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalised copy of each university's most recent CENSIS standing, so
 * list/sort/filter contexts (FindUniversities, CompareShortlist) never call
 * University::latestRanking() per row. Kept in sync by
 * University::syncAllLatestRankingColumns() — run from
 * UniversityRankingSeeder and SeedUniversityRankingsJob.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->unsignedSmallInteger('latest_ranking_position')->nullable()->after('logo');
            $table->string('latest_ranking_category')->nullable()->after('latest_ranking_position');
            $table->string('latest_ranking_edition')->nullable()->after('latest_ranking_category');
            $table->decimal('latest_ranking_overall_score', 4, 1)->nullable()->after('latest_ranking_edition');

            $table->index('latest_ranking_position');
        });

        University::syncAllLatestRankingColumns();
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropIndex(['latest_ranking_position']);
            $table->dropColumn([
                'latest_ranking_position',
                'latest_ranking_category',
                'latest_ranking_edition',
                'latest_ranking_overall_score',
            ]);
        });
    }
};
