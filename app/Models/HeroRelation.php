<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroRelation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['note' => 'array'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public function relatedHero()
    {
        return $this->belongsTo(Hero::class, 'related_hero_id');
    }
}
