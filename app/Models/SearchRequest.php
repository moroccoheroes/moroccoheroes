<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Normalizer;

/**
 * Une recherche restee sans resultat.
 *
 * Les variantes d'une meme demande sont ramenees a une cle unique :
 * "Said Aouita", "said aouita" et "  SAID  AOUITA " comptent pour une
 * seule ligne. Sans cela, l'administrateur verrait six demandes la ou
 * il n'y en a qu'une, et le compteur ne voudrait plus rien dire.
 */
class SearchRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_searched_at' => 'datetime'];
    }

    // ---------- Enregistrement ----------

    /**
     * Enregistre une demande, ou incremente celle qui existe deja.
     */
    public static function record(string $query, ?int $userId = null, ?string $locale = null): self
    {
        $request = static::firstOrCreate(
            ['normalized_query' => static::normalize($query)],
            [
                'query'      => trim($query),
                'user_id'    => $userId,
                'locale'     => $locale ?? app()->getLocale(),
                'hits_count' => 0,
            ],
        );

        $request->increment('hits_count', 1, ['last_searched_at' => now()]);

        return $request;
    }

        /**
     * Marque la demande correspondante comme traitee et la relie a la fiche.
     *
     * Le rapprochement passe par la cle normalisee : l'administrateur peut
     * lancer la recherche avec une casse ou des accents differents de ce
     * qu'avait tape le visiteur, les deux retombent sur la meme cle.
     *
     * Renvoie null quand aucune demande ne correspond : une fiche creee
     * a l'initiative de l'administrateur n'a pas de demande derriere elle.
     */
    public static function resolveFor(string $query, Hero $hero): ?self
    {
        $request = static::where('normalized_query', static::normalize($query))->first();

        if ($request === null) {
            return null;
        }

        $request->update([
            'status'  => 'resolved',
            'hero_id' => $hero->id,
        ]);

        return $request;
    }

    /**
     * Ramene une requete a sa forme de comparaison.
     *
     * La decomposition Unicode separe les lettres de leurs signes
     * diacritiques, que l'on retire ensuite : "Saïd" devient "said",
     * et les harakat arabes disparaissent sans abimer les lettres.
     * On ne translittere pas : l'arabe doit rester de l'arabe, sinon
     * toutes les requetes arabes se confondraient en une seule cle.
     */
    public static function normalize(string $query): string
    {
        $value = trim($query);

        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_D) ?: $value;
            $value = preg_replace('/\p{Mn}+/u', '', $value);
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    // ---------- Relations ----------

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ---------- Scopes ----------

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    /** L'ordre de travail de l'administrateur : le plus demande d'abord. */
    public function scopePopular(Builder $q): Builder
    {
        return $q->orderByDesc('hits_count')->orderByDesc('last_searched_at');
    }
}