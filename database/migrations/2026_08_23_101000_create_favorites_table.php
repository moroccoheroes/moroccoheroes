<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Heros mis en favori par un utilisateur connecte. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'hero_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
