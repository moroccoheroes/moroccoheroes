<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();

            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->longText('content');

            // Tracabilite : quels extraits ont servi a construire la reponse
            $table->json('citations')->nullable();

            $table->string('model')->nullable();       // ex: claude-sonnet-4-6
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();

            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
