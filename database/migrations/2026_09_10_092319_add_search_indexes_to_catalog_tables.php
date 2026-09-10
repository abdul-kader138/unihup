<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering indexes for the columns App\Filament\Pages\FindUniversities
 * filters and sorts on. Every SelectFilter there (degree_level, language,
 * admission_type) and the default `university.name` sort was an unindexed
 * scan before this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->index('name');
            $table->index('city');
            $table->index('region');
        });

        Schema::table('degree_programs', function (Blueprint $table) {
            $table->index('degree_level');
            $table->index('language');
            $table->index('admission_type');
            // The common FindUniversities combination: a saved subject
            // preference + degree level + language filter.
            $table->index(['subject_id', 'degree_level', 'language'], 'degree_programs_filter_idx');
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['city']);
            $table->dropIndex(['region']);
        });

        Schema::table('degree_programs', function (Blueprint $table) {
            $table->dropIndex(['degree_level']);
            $table->dropIndex(['language']);
            $table->dropIndex(['admission_type']);
            $table->dropIndex('degree_programs_filter_idx');
        });
    }
};
