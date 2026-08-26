<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('degree_programs', function (Blueprint $table) {
            // Natural key for bulk upserts (see App\Services\Universities\MurUstatImporter) —
            // without a real unique index, DB-level upsert() can't dedupe on MySQL.
            $table->unique(['university_id', 'subject_id', 'degree_level', 'name'], 'degree_programs_natural_key');
        });
    }

    public function down(): void
    {
        Schema::table('degree_programs', function (Blueprint $table) {
            $table->dropUnique('degree_programs_natural_key');
        });
    }
};
