<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Table pivot many-to-many : un heros a plusieurs tags, et inversement. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_tag', function (Blueprint $table) {
            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['hero_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_tag');
    }
};
