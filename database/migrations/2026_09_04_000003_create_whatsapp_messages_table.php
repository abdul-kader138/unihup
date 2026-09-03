<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            // 'in' = from the customer, 'out' = sent by staff / the system.
            $table->string('direction');
            // Meta's message id (wamid...). Unique so retried webhooks and
            // status callbacks don't create duplicates. Null only briefly for
            // an outbound row between insert and the API accepting it.
            $table->string('wa_message_id')->nullable()->unique();
            $table->string('type')->default('text'); // text | image | document | audio | video | template
            $table->text('body')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_mime')->nullable();
            // queued | sent | delivered | read | failed
            $table->string('status')->default('queued');
            $table->text('error')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
