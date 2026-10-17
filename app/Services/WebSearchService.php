<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Recherche web et premier filtrage des pages.
 *
 * La recherche se fait en deux passages : d'abord sur des domaines de
 * reference, ensuite sur tout le web si le premier passage ne donne pas
 * assez. Sans cette contrainte, une requete sur un athlete a deja
 * renvoye un site de citations, une affaire de divorce et un annuaire
 * de statistiques : aucune source biographique.
 */
class WebSearchService
{
    /**
     * Domaines ecartes. Ce ne sont pas des sources recevables pour un
     * site institutionnel, quelle que soit la quantite de texte servie.
     */
    private const BLOCKED = [
        // reseaux sociaux
        'instagram.com', 'facebook.com', 'x.com', 'twitter.com',
        'tiktok.com', 'pinterest.com', 'linkedin.com',

        // banques d'images : beaucoup de texte, aucune information
        'gettyimages.com', 'shutterstock.com', 'alamy.com',
        'istockphoto.com', 'depositphotos.com',

        // dictionnaires, citations, forums, miroirs de Wikipedia
        'thefreedictionary.com', 'azquotes.com', 'brainyquote.com',
        'kiddle.co', 'dbpedia.org', '/forum',
    ];

    /** Sources institutionnelles et encyclopediques, interrogees en premier. */
    private const PREFERRED_DOMAINS = [
        'wikipedia.org',
        'worldathletics.org',
        'olympics.com',
        'britannica.com',
        'unesco.org',
    ];

    /** Longueur minimale pour qu'une page soit exploitable. */
    private const MIN_LENGTH = 300;

    /** En dessous de ce nombre de pages, on rouvre la recherche a tout le web. */
    private const MIN_PREFERRED_RESULTS = 2;

    /**
     * Cherche sur le web et renvoie les pages utilisables.
     *
     * @return Collection<int, array{url: string, title: string, content: string}>
     */
    public function search(string $query, int $maxResults = 5): Collection
    {
        $pages = $this->call($query, $maxResults, self::PREFERRED_DOMAINS);

        // Les domaines de reference ne couvrent pas toutes les figures.
        if ($pages->count() < self::MIN_PREFERRED_RESULTS) {
            $pages = $pages
                ->concat($this->call($query, $maxResults))
                ->unique('url')
                ->values();
        }

        return $pages;
    }

    /**
     * @return Collection<int, array{url: string, title: string, content: string}>
     */
    private function call(string $query, int $maxResults, array $includeDomains = []): Collection
    {
        $payload = [
            'query'               => $query,
            'max_results'         => $maxResults,
            'include_raw_content' => true,
        ];

        if ($includeDomains !== []) {
            $payload['include_domains'] = $includeDomains;
        }

                $response = Http::withToken(config('services.tavily.key'))
            ->connectTimeout(20)
            ->timeout(45)
            ->retry(3, 2000, throw: false)
            ->post('https://api.tavily.com/search', $payload);

        $response->throw();

        return collect($response->json('results'))
            ->map(fn (array $r) => [
                'url'   => $r['url'],
                'title' => $r['title'] ?? '',
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