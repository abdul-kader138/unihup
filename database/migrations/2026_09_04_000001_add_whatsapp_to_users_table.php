<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // E.164 (e.g. +393331234567). Kept separate from `phone` so a
            // student can be reachable on WhatsApp at a different number.
            $table->string('whatsapp_number')->nullable()->after('phone');
            $table->boolean('whatsapp_opt_in')->default(false)->after('whatsapp_number');
            $table->timestamp('whatsapp_opt_in_at')->nullable()->after('whatsapp_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_number', 'whatsapp_opt_in', 'whatsapp_opt_in_at']);
        });
    }
};
