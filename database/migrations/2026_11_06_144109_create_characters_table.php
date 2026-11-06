<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('anilist_id')->unique();
            $table->foreignId('anime_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('search_name')->index();
            $table->json('alternative_names');
            $table->string('image_path');
            $table->unsignedInteger('favourites')->default(0)->index();
            $table->string('difficulty')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
