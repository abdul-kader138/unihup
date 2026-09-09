<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('degree_programs', function (Blueprint $table) {
            // Structured annual tuition range in EUR, when known — powers
            // App\Support\CostEstimator. `tuition_note` stays as the free-text
            // caveat ("scales by ISEE", "first year only", ...).
            $table->decimal('tuition_min', 8, 2)->nullable()->after('tuition_note');
            $table->decimal('tuition_max', 8, 2)->nullable()->after('tuition_min');
        });
    }

    public function down(): void
    {
        Schema::table('degree_programs', function (Blueprint $table) {
            $table->dropColumn(['tuition_min', 'tuition_max']);
        });
    }
};
