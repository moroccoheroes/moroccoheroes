<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroTranslation extends Model
{
    protected $guarded = [];

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
