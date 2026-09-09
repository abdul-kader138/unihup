<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ISO 4217 code the student thinks in — used by the Budget Planner
            // to show a second, home-currency column next to the euro figures.
            $table->string('home_currency', 3)->nullable()->after('scholarship_interest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('home_currency');
        });
    }
};
