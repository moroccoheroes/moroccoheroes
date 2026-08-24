<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'event_date' => 'date'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
