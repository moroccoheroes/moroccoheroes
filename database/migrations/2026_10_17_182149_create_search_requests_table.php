<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recherches restees sans resultat.
 *
 * Une ligne par demande distincte, pas par recherche : les variantes
 * d'une meme requete ("Said Aouita", "said aouita", "SAID AOUITA")
 * sont ramenees a une seule cle et incrementent le meme compteur.
 * C'est ce compteur qui donne a l'administrateur son ordre de travail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_requests', function (Blueprint $table) {
            $table->id();

            // Ce que l'utilisateur a tape, conserve tel quel pour l'affichage.
            $table->string('query');

            // La cle de regroupement : minuscules, sans accent, espaces normalises.
            $table->string('normalized_query')->unique();

            $table->unsignedInteger('hits_count')->default(1);

            $table->enum('status', ['pending', 'processing', 'resolved', 'rejected'])
                  ->default('pending');

            // Renseigne quand la demande a abouti a une fiche.
            $table->foreignId('hero_id')->nullable()->constrained()->nullOnDelete();

            // Qui a cherche en premier. Null pour un visiteur non connecte.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Langue de l'interface au moment de la recherche.
            $table->string('locale', 5)->nullable();

            $table->timestamp('last_searched_at')->nullable();

            // Note du moderateur quand il rejette une demande.
            $table->text('review_note')->nullable();

            $table->timestamps();

            // L'ecran d'administration trie par popularite dans les demandes en attente.
            $table->index(['status', 'hits_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_requests');
    }
};