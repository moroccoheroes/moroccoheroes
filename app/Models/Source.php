<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'is_verified' => 'boolean'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
