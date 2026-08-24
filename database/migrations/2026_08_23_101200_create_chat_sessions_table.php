<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Une conversation avec l'agent IA (globale ou centree sur un heros). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // identifiant expose dans l'URL

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hero_id')->nullable()->constrained()->nullOnDelete();

            $table->string('locale', 5)->default('fr');
            $table->string('title')->nullable();      // resume auto de la conversation
            $table->string('session_token')->nullable(); // visiteurs non connectes
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
