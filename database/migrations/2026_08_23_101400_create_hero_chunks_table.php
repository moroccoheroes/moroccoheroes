<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de connaissances de l'agent IA (RAG).
 * Chaque fiche heros est decoupee en morceaux de texte ("chunks"),
 * chacun converti en vecteur (embedding) pour la recherche semantique.
 *
 * MySQL : on stocke l'embedding en JSON et on calcule la similarite en PHP
 *         (suffisant jusqu'a quelques milliers de chunks).
 * PostgreSQL + pgvector : remplacer par une colonne `vector(1536)` -> bien plus rapide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);

            $table->enum('source_type', ['biography', 'achievement', 'timeline', 'summary', 'source'])
                ->default('biography');
            $table->unsignedBigInteger('source_id')->nullable(); // id de la ligne d'origine

            $table->text('content');
            $table->json('embedding')->nullable();
            $table->unsignedInteger('token_count')->nullable();
            $table->string('checksum', 64)->nullable(); // evite de re-embedder si inchange

            $table->timestamps();

            $table->index(['hero_id', 'locale']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_chunks');
    }
};
