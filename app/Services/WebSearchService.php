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
        'instagram.com', 'facebook.com', 'x.com', 'twitter.com',
        'tiktok.com', 'pinterest.com', 'linkedin.com',
    ];

    /** Longueur minimale pour qu'une page soit exploitable. */
    private const MIN_LENGTH = 500;

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
            ->filter(fn (array $r) => strlen($r['raw_content'] ?? '') > self::MIN_LENGTH)
            ->reject(fn (array $r) => $this->isBlocked($r['url']))
            ->map(fn (array $r) => [
                'url'     => $r['url'],
                'title'   => $r['title'],
                'content' => $r['raw_content'],
            ])
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