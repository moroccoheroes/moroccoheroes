<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relie les heros entre eux : mentor, coequipier, rival, famille...
 * Permet la section "Figures liees" sur la fiche + un graphe de reseau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->foreignId('related_hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->enum('relation_type', [
                'mentor', 'student', 'teammate', 'rival',
                'family', 'contemporary', 'influenced_by',
            ]);
            $table->json('note')->nullable(); // precision traduisible
            $table->timestamps();

            $table->unique(['hero_id', 'related_hero_id', 'relation_type'], 'hero_relation_unique');
            $table->index('related_hero_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_relations');
    }
};
