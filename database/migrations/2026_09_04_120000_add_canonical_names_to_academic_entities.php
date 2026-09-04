<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Subject;
use App\Models\University;
use App\Support\CanonicalAcademicNames;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->string('canonical_name')->nullable()->after('name')->index();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('canonical_name')->nullable()->after('name')->index();
        });

        University::query()->each(function (University $university) {
            $university->update(['canonical_name' => CanonicalAcademicNames::university($university->name)]);
        });

        Subject::query()->each(function (Subject $subject) {
            $subject->update(['canonical_name' => CanonicalAcademicNames::subject($subject->name)]);
        });
    }

    public function down(): void
    {
        Schema::table('universities', fn (Blueprint $table) => $table->dropColumn('canonical_name'));
        Schema::table('subjects', fn (Blueprint $table) => $table->dropColumn('canonical_name'));
    }
};
