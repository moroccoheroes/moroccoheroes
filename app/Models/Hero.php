<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Hero extends Model
{
    use HasFactory, HasTranslations, Searchable, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'published_at' => 'datetime',
            'indexed_at' => 'datetime',
            'is_alive' => 'boolean',
            'is_featured' => 'boolean',
            'ai_chat_enabled' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------- Relations ----------

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class)->orderBy('year');
    }

    public function timelineEvents()
    {
        return $this->hasMany(TimelineEvent::class)->orderBy('year')->orderBy('sort_order');
    }

    public function media()
    {
        return $this->hasMany(Media::class)->orderBy('sort_order');
    }

    public function sources()
    {
        return $this->hasMany(Source::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function chunks()
    {
        return $this->hasMany(HeroChunk::class);
    }

    public function relations()
    {
        return $this->hasMany(HeroRelation::class);
    }

    public function relatedHeroes()
    {
        return $this->belongsToMany(Hero::class, 'hero_relations', 'hero_id', 'related_hero_id')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---------- Scopes ----------

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    public function scopeChattable(Builder $q): Builder
    {
        return $q->published()->where('ai_chat_enabled', true);
    }

    public function scopeInCategory(Builder $q, Category $category): Builder
    {
        return $q->whereIn('category_id', $category->descendantIds());
    }

    // ---------- Accessors ----------

    public function name(): ?string
    {
        return $this->tr('name');
    }

    public function getYearsAttribute(): string
    {
        $birth = $this->birth_year ?? $this->birth_date?->year;
        $death = $this->death_year ?? $this->death_date?->year;

        if (! $birth) {
            return '—';
        }

        return $this->is_alive ? "{$birth} –" : "{$birth} – ".($death ?? '?');
    }

    // ---------- Scout (Meilisearch) ----------

    public function toSearchableArray(): array
    {
        $this->loadMissing('translations', 'category.translations');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_ar' => $this->tr('name', 'ar'),
            'name_fr' => $this->tr('name', 'fr'),
            'name_en' => $this->tr('name', 'en'),
            'nickname' => $this->translations->pluck('nickname')->filter()->values()->all(),
            'summary' => $this->translations->pluck('summary')->filter()->values()->all(),
            'category' => $this->category?->slug,
            'birth_year' => $this->birth_year ?? $this->birth_date?->year,
            'is_alive' => $this->is_alive,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }
}
