<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galerie (photos, videos, documents d'archives).
 * NB : si tu installes spatie/laravel-medialibrary, tu peux supprimer
 * cette migration et utiliser sa table `media`. Ici version simple.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['image', 'video', 'document', 'audio'])->default('image');
            $table->string('disk')->default('public');
            $table->string('path');                       // ou URL YouTube pour type=video
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->json('caption')->nullable();          // legende traduisible
            $table->string('credit')->nullable();         // "Photo : MAP"
            $table->string('source_url')->nullable();
            $table->string('license')->nullable();        // droits d'usage

            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['hero_id', 'type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
