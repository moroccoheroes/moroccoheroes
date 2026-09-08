<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class WebSearchService
{
    /**
     * Domaines ecartes : reseaux sociaux et agregateurs.
     * Ce ne sont pas des sources acceptables pour un site institutionnel.
     */
    private const BLOCKED = [
    // reseaux sociaux
    'instagram.com', 'facebook.com', 'x.com', 'twitter.com',
    'tiktok.com', 'pinterest.com', 'linkedin.com',

    // banques d'images
    'gettyimages.com', 'shutterstock.com', 'alamy.com',
    'istockphoto.com', 'depositphotos.com',

    // dictionnaires, citations, forums, miroirs de Wikipedia
    'thefreedictionary.com', 'azquotes.com', 'brainyquote.com',
    'kiddle.co', 'dbpedia.org', '/forum',
];
    /** Longueur minimale pour qu'une page soit exploitable. */
    private const MIN_LENGTH = 300;

    /**
     * Cherche sur le web et renvoie les pages utilisables.
     *
     * @return Collection<int, array{url: string, title: string, content: string}>
     */
   public function search(string $query, int $maxResults = 5): Collection
{
    $response = Http::withToken(config('services.tavily.key'))
        ->timeout(30)
        ->post('https://api.tavily.com/search', [
            'query'               => $query,
            'max_results'         => $maxResults,
            'include_raw_content' => true,
        ]);

    $response->throw();

    return collect($response->json('results'))
        ->map(fn (array $r) => [
            'url'     => $r['url'],
            'title'   => $r['title'] ?? '',
            // raw_content n'est pas toujours renvoye ; content est alors
            // le seul texte disponible et suffit souvent.
            'content' => filled($r['raw_content'] ?? null)
                ? $r['raw_content']
                : ($r['content'] ?? ''),
        ])
        ->filter(fn (array $r) => strlen($r['content']) > self::MIN_LENGTH)
        ->reject(fn (array $r) => $this->isBlocked($r['url']))
        ->values();
}

    private function isBlocked(string $url): bool
    {
        foreach (self::BLOCKED as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        return false;
    }
}