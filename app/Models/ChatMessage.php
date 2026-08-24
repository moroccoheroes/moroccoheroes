<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['citations' => 'array'];
    }

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function scopeVisible($q)
    {
        return $q->whereIn('role', ['user', 'assistant']);
    }
}
