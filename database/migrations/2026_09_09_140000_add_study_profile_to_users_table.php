<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drives the personalised journey checklist (App\Support\JourneyTemplate)
            // and the "can I apply?" hints (App\Support\EligibilityEngine).
            $table->string('nationality')->nullable()->after('preferred_degree_level');
            // Asked directly rather than derived from `nationality` — this is the
            // fork that decides whether the visa / pre-enrolment / permesso steps
            // apply. Defaults false = treat as non-EU (the safer default: shows
            // the extra steps rather than hiding them).
            $table->boolean('is_eu_citizen')->default(false)->after('nationality');
            $table->string('prior_education_country')->nullable()->after('is_eu_citizen');
            // CEFR-ish level keys from App\Support\ProficiencyLevels.
            $table->string('english_level')->nullable()->after('prior_education_country');
            $table->string('italian_level')->nullable()->after('english_level');
            $table->boolean('scholarship_interest')->default(false)->after('italian_level');
            $table->timestamp('study_profile_completed_at')->nullable()->after('scholarship_interest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nationality', 'is_eu_citizen', 'prior_education_country',
                'english_level', 'italian_level', 'scholarship_interest',
                'study_profile_completed_at',
            ]);
        });
    }
};
