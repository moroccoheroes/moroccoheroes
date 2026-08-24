<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function heroes()
    {
        return $this->belongsToMany(Hero::class);
    }
}
