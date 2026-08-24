<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);

            $table->string('name');                       // "أحمد فرس" / "Ahmed Faras"
            $table->string('nickname')->nullable();       // surnom
            $table->string('birth_place')->nullable();    // "الدار البيضاء" / "Casablanca"
            $table->string('death_place')->nullable();

            $table->text('summary')->nullable();          // 2-3 phrases (cartes, listes)
            $table->longText('biography')->nullable();    // biographie complete

            $table->string('meta_title')->nullable();     // SEO
            $table->string('meta_description', 500)->nullable();

            $table->timestamps();

            $table->unique(['hero_id', 'locale']);
            $table->index(['locale', 'name']); // tri alphabetique par langue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_translations');
    }
};
