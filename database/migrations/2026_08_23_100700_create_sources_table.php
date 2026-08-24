<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sources / references bibliographiques.
 * Table STRATEGIQUE : c'est elle qui rend l'agent IA credible
 * (chaque reponse du chatbot pourra citer une source verifiee).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['book', 'article', 'website', 'archive', 'interview', 'video', 'official', 'other'])
                ->default('other');

            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->smallInteger('published_year')->nullable();
            $table->string('url', 1000)->nullable();

            $table->boolean('is_primary')->default(false); // source primaire ?
            $table->boolean('is_verified')->default(false); // validee par un moderateur

            $table->timestamps();

            $table->index('hero_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
