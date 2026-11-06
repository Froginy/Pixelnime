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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->uuid('player_uuid')->index();
            $table->string('mode');
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_challenge_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('guesses');
            $table->string('status')->default('playing');
            $table->timestamps();

            $table->unique(['player_uuid', 'daily_challenge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
