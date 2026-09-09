<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regional_scholarships', function (Blueprint $table) {
            // Typical annual award range in EUR and the household ISEE ceiling
            // for eligibility, when published — used by App\Support\CostEstimator
            // to net a scholarship off the estimated yearly cost.
            $table->decimal('amount_min', 8, 2)->nullable()->after('description');
            $table->decimal('amount_max', 8, 2)->nullable()->after('amount_min');
            $table->decimal('isee_threshold', 10, 2)->nullable()->after('amount_max');
        });
    }

    public function down(): void
    {
        Schema::table('regional_scholarships', function (Blueprint $table) {
            $table->dropColumn(['amount_min', 'amount_max', 'isee_threshold']);
        });
    }
};
