<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un fragment de texte indexe pour le RAG. Le checksum evite de payer
 * un nouvel embedding quand le contenu n'a pas bouge.
 */
class HeroChunk extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public static function checksumFor(string $content): string
    {
        return hash('sha256', trim($content));
    }
}
