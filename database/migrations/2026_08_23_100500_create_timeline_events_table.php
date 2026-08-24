<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Chronologie de la vie du heros (affichee en frise sur la fiche). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();

            $table->date('event_date')->nullable();
            $table->smallInteger('year')->nullable();

            $table->json('title');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['hero_id', 'year', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
