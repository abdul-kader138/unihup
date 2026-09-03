<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            // The customer's WhatsApp number as Meta reports it (E.164, no '+').
            // This is the stable key we match inbound webhooks on; `user_id` is
            // filled in when we can tie the number to an account.
            $table->string('wa_contact_id')->unique();
            $table->string('wa_contact_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            // open = needs attention, pending = waiting on customer, closed = done.
            $table->string('status')->default('open');
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_inbound_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
