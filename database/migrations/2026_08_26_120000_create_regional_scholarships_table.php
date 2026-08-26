<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            // Several regions run more than one body (Veneto, Sicilia, Sardegna,
            // Trentino-Alto Adige each split by province/university), so
            // (region, body_name) is the natural key, not region alone.
            $table->string('body_name');
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['region', 'body_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_scholarships');
    }
};
