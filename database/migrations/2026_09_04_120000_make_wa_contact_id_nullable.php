<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support Chat (App\Filament\Pages\SupportChat) lets a signed-in user without
 * a WhatsApp number chat portal-only — that conversation row has no real
 * WhatsApp contact yet, so wa_contact_id can no longer be a required column.
 * The unique index is untouched: MySQL/Postgres both allow any number of
 * NULLs alongside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->string('wa_contact_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->string('wa_contact_id')->nullable(false)->change();
        });
    }
};
