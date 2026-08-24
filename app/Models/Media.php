<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['caption' => 'array', 'is_cover' => 'boolean'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
