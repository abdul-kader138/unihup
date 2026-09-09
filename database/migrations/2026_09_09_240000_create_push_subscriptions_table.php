<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The push service endpoint can be long, so it is not indexed
            // directly — endpoint_hash (sha256 hex) carries the uniqueness.
            $table->text('endpoint');
            $table->string('endpoint_hash', 64);
            $table->string('public_key');   // client p256dh
            $table->string('auth_token');   // client auth secret
            $table->string('content_encoding')->nullable();
            $table->timestamps();

            $table->unique('endpoint_hash');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
