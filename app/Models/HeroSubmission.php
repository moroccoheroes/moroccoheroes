<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSubmission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }
}
