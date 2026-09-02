<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Hero;
use Illuminate\Support\Facades\DB;

/**
 * Enregistre une fiche extraite, toujours en brouillon.
 *
 * Rien de ce qui vient de l'extraction automatique n'est publie
 * directement : la fiche attend une relecture humaine.
 */
class HeroPersister
{
    public function save(array $data): Hero
    {
        return DB::transaction(function () use ($data) {
            $hero = Hero::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id'     => $this->categoryId($data['category'] ?? null),
                    'gender'          => $data['gender'] ?? null,
                    'birth_date'      => $data['birth_date'] ?? null,
                    'birth_year'      => $data['birth_year'] ?? null,
                    'death_year'      => $data['death_year'] ?? null,
                    'is_alive'        => $data['is_alive'] ?? true,
                    'status'          => 'draft',
                    'ai_chat_enabled' => false,
                ],
            );

            $this->saveTranslations($hero, $data['translations'] ?? []);
            $this->saveAchievements($hero, $data['achievements'] ?? []);
            $this->saveSources($hero, $data['sources'] ?? []);

            return $hero->load('translations', 'achievements', 'sources');
        });
    }

    private function categoryId(?string $slug): ?int
    {
        return $slug ? Category::where('slug', $slug)->value('id') : null;
    }

    /**
     * Les lignes entierement vides ne sont pas ecrites.
     *
     * Le modele renvoie zgh avec tous ses champs a null. Inserer cette
     * ligne casserait le fallback : tr('name') trouverait la ligne zgh,
     * lirait null, et renverrait null sans jamais essayer le francais.
     */
    private function saveTranslations(Hero $hero, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            $fields = array_filter($fields, fn ($v) => filled($v));

            if ($fields === []) {
                continue;
            }

            $hero->translations()->updateOrCreate(['locale' => $locale], $fields);
        }
    }

    private function saveAchievements(Hero $hero, array $achievements): void
    {
        $hero->achievements()->delete();

        foreach ($achievements as $i => $a) {
            if (blank($a['title'] ?? null)) {
                continue;
            }

            $hero->achievements()->create([
                'year'       => $a['year'] ?? null,
                'type'       => $a['type'] ?? 'other',
                'title'      => $a['title'],
                'sort_order' => $i,
            ]);
        }
    }

    private function saveSources(Hero $hero, array $urls): void
{
    $hero->sources()->whereNotIn('url', $urls)->delete();

    foreach ($urls as $url) {
        $hero->sources()->updateOrCreate(
            ['url' => $url],
            [
                'title'       => parse_url($url, PHP_URL_HOST),
                'is_verified' => false,
            ],
        );
    }
}
}