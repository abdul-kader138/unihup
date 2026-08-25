<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_subject_id')->nullable()->after('marketing_opt_in')
                ->constrained('subjects')->nullOnDelete();
            // 'bachelor' or 'master' — see database/migrations/*_create_degree_programs_table.
            $table->string('preferred_degree_level')->nullable()->after('preferred_subject_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_subject_id');
            $table->dropColumn('preferred_degree_level');
        });
    }
};
