<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function heroes()
    {
        return $this->hasMany(Hero::class);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id')->orderBy('sort_order');
    }

    /** Ids de la categorie + toutes ses sous-categories (filtrage "Sport" = tout le sport). */
    public function descendantIds(): array
    {
        return [$this->id, ...$this->children()->pluck('id')->all()];
    }
}
