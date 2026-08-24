<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propositions du public : ajouter un heros ou corriger une fiche.
 * Le contenu propose est stocke en JSON (payload) tant qu'il n'est pas valide :
 * on ne touche JAMAIS aux tables publiees avant moderation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_submissions', function (Blueprint $table) {
            $table->id();

            // NULL = proposition d'un nouveau heros ; sinon = correction d'un existant
            $table->foreignId('hero_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contributor_name')->nullable();  // si non connecte
            $table->string('contributor_email')->nullable();

            $table->json('payload');                          // donnees proposees
            $table->text('message')->nullable();              // mot du contributeur

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_submissions');
    }
};
