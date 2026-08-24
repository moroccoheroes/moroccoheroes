<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gere les modeles dont le texte vit dans une table `xxx_translations`.
 *
 *   $hero->tr('name')              -> langue courante, sinon fallback
 *   $hero->tr('name', 'ar')        -> force l'arabe
 *   Hero::withTranslation()->get() -> evite le N+1
 *
 * Le fallback se fait CHAMP par CHAMP : si la fiche amazighe existe mais que
 * sa biographie est vide, on retombe sur la biographie francaise plutot que
 * de renvoyer du vide.
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel(), $this->translationForeignKey());
    }

    public function scopeWithTranslation($query, ?string $locale = null)
    {
        return $query->with('translations');
    }

    public function tr(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $fallback = config('app.fallback_locale', 'fr');

        foreach (array_unique([$locale, $fallback]) as $try) {
            $value = $this->translations->firstWhere('locale', $try)?->{$field};

            if (filled($value)) {
                return $value;
            }
        }

        return $this->translations->pluck($field)->filter()->first();
    }

    protected function translationModel(): string
    {
        return static::class.'Translation';
    }

    protected function translationForeignKey(): string
    {
        return str(class_basename(static::class))->snake()->toString().'_id';
    }
}