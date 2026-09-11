<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set once, programmatically, in User::booted() — never
            // user-fillable (not in the #[Fillable] list on the model).
            $table->string('referral_code')->nullable()->unique()->after('journey_last_active_date');

            // Self-referencing: who invited this user, if anyone. Captured
            // at registration from the ?ref= query string — see
            // App\Filament\Auth\Register.
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn('referral_code');
        });
    }
};
