<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
