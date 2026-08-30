<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Transforme des pages web nettoyees en fiche structuree.
 *
 * La consigne centrale est de ne rien inventer : tout champ absent
 * des sources doit ressortir a null. Une fiche publiee sur un site
 * du Ministere engage l'institution.
 */
class HeroExtractor
{
    public function extract(string $query, Collection $pages): array
    {
        $context = $pages
            ->map(fn (array $p) => "SOURCE: {$p['url']}\n\n{$p['content']}")
            ->implode("\n\n---\n\n");

        $response = Http::timeout(120)
            ->withToken(config('services.groq.key'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'           => config('services.groq.model'),
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => $this->instructions()],
                    ['role' => 'user',   'content' => "FIGURE RECHERCHEE : {$query}\n\nSOURCES :\n\n{$context}"],
                ],
            ]);

        $response->throw();

        $json = $response->json('choices.0.message.content');
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('Reponse du modele illisible : '.mb_substr((string) $json, 0, 200));
        }

        return $data;
    }

    private function instructions(): string
    {
        return <<<'TXT'
        Tu extrais une fiche biographique a partir des sources fournies.

        REGLES
        - N'invente rien. Toute information absente des sources vaut null.
        - Ne deduis pas une date a partir d'un age approximatif.
        - Si la personne est decedee, renseigne death_year et is_alive = false.
        - Traduis toi-meme en ar et en en a partir du francais.
        - Le champ zgh reste entierement null : la transcription des noms
          propres en tifinagh n'a pas de norme stable et sera saisie a la main.
        - sources ne contient que les URL reellement utilisees.

        Reponds uniquement avec cet objet JSON :

        {
          "slug": "prenom-nom",
          "gender": "male|female",
          "birth_date": "YYYY-MM-DD ou null",
          "birth_year": 1946,
          "death_year": null,
          "is_alive": true,
          "category": "football|athletisme|boxe|basketball|arts-martiaux|litterature|musique|cinema|arts-visuels|resistance|dynasties|exploration|sciences|spiritualite|education",
          "translations": {
            "fr":  {"name": "", "nickname": null, "birth_place": "", "summary": ""},
            "ar":  {"name": "", "nickname": null, "birth_place": "", "summary": ""},
            "en":  {"name": "", "nickname": null, "birth_place": "", "summary": ""},
            "zgh": {"name": null, "nickname": null, "birth_place": null, "summary": null}
          },
          "achievements": [
            {"year": 1975, "type": "award", "title": {"fr": "", "ar": "", "en": ""}}
          ],
          "sources": []
        }
        TXT;
    }
}