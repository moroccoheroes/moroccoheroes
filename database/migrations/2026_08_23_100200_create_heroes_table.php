<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table centrale. Elle ne contient QUE les donnees non traduisibles
 * (dates, statut, compteurs). Tout le texte est dans hero_translations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique(); // ex: "ahmed-faras"

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('gender', ['male', 'female'])->nullable();

            // --- Dates : historiquement souvent imprecises ---
            $table->date('birth_date')->nullable();
            $table->smallInteger('birth_year')->nullable(); // si seule l'annee est connue
            $table->date('death_date')->nullable();
            $table->smallInteger('death_year')->nullable();
            $table->enum('date_precision', ['day', 'month', 'year', 'circa', 'unknown'])
                ->default('day');
            $table->boolean('is_alive')->default(true);

            $table->string('cover_image')->nullable(); // photo principale

            // --- Workflow editorial (important pour un ministere) ---
            $table->enum('status', ['draft', 'pending', 'published', 'rejected'])
                ->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->unsignedBigInteger('views_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // suppression reversible

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
