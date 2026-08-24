<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exploits / titres / oeuvres.
 * title et description sont des colonnes JSON traduisibles :
 *   {"ar": "كأس إفريقيا 1976", "fr": "CAN 1976", "en": "AFCON 1976"}
 * Gerees par spatie/laravel-translatable (propriete $translatable du modele).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['award', 'title', 'record', 'publication', 'work', 'other'])
                ->default('other');

            $table->smallInteger('year')->nullable();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['hero_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
