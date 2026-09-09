<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_guides', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('slug')->unique();
            $table->string('region')->nullable();
            $table->text('intro')->nullable();
            $table->text('housing')->nullable();
            $table->text('cost_of_living')->nullable();
            $table->text('transport')->nullable();
            $table->text('student_life')->nullable();
            $table->text('safety')->nullable();
            // [{label, url}, ...]
            $table->json('useful_links')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_guides');
    }
};
