<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($s) => $s->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }
}
