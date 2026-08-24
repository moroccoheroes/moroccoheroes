<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs de pilotage de l'agent IA.
 *
 * Regle editoriale : le chat n'est JAMAIS actif par defaut. Un moderateur
 * doit l'activer explicitement, et seulement si la fiche possede assez de
 * sources verifiees. Pour une personne vivante, le mode reste 'biographical'
 * (l'agent parle DE la personne, il ne se fait pas passer POUR elle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->boolean('ai_chat_enabled')->default(false)->after('is_featured');
            $table->enum('ai_chat_mode', ['biographical', 'persona'])
                ->default('biographical')->after('ai_chat_enabled');
            $table->text('ai_persona_notes')->nullable()->after('ai_chat_mode');
            $table->text('ai_forbidden_topics')->nullable()->after('ai_persona_notes');
            $table->timestamp('indexed_at')->nullable()->after('ai_forbidden_topics');
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn([
                'ai_chat_enabled', 'ai_chat_mode', 'ai_persona_notes',
                'ai_forbidden_topics', 'indexed_at',
            ]);
        });
    }
};
