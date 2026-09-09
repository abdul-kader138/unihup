<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_check_results', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024);
            // sha1 of the url — the real unique key (a url column can't be
            // uniquely indexed at full length on MySQL).
            $table->string('url_hash', 40)->unique();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('ok')->default(false);
            $table->string('error')->nullable();
            // A human pointer to where the link lives, e.g. "DegreeProgram #42 official_admission_url".
            $table->string('source')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_check_results');
    }
};
