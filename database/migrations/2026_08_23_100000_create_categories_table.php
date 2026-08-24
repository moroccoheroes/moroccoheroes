<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories = Sport, Culture, Science, Histoire, Resistance...
 * Sous-categories = Football, Boxe, Basket / Ecrivain, Cineaste...
 * Une seule table auto-referencee (parent_id) au lieu de deux tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // parent_id NULL = categorie racine ; sinon = sous-categorie
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->string('slug')->unique();   // ex: "football", "boxe"
            $table->string('icon')->nullable(); // nom d'icone Lucide cote React
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
