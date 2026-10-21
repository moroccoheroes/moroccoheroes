<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Recherche web et premier tri des pages.
 *
 * Deux passages : d'abord les domaines de reference, puis le web ouvert.
 * Les deux sont systematiques. Un premier passage restreint ne renvoie
 * pas moins de resultats quand la figure ne s'y trouve pas, il en
 * renvoie de hors sujet : une recherche sur une sociologue a ramene la
 * page d'accueil de Britannica et deux articles sur les Jeux Olympiques.
 *
 * Le tri final ne fait donc pas confiance au moteur : une page doit
 * nommer la personne pour etre retenue, et plus elle la nomme, mieux
 * elle est classee.
 */
class WebSearchService
{
    private const BLOCKED = [
        'instagram.com', 'facebook.com', 'x.com', 'twitter.com',
        'tiktok.com', 'pinterest.com', 'linkedin.com', 'youtube.com',
        'gettyimages.com', 'shutterstock.com', 'alamy.com',
        'istockphoto.com', 'depositphotos.com',
        'thefreedictionary.com', 'azquotes.com', 'brainyquote.com',
        'kiddle.co', 'dbpedia.org', '/forum',
    ];

    /** Encyclopedies et institutions, tous domaines confondus. */
    private const PREFERRED_DOMAINS = [
        'wikipedia.org',
        'britannica.com',
        'unesco.org',
        'persee.fr',
        'cairn.info',
    ];

    private const MIN_LENGTH = 300;

    /** Longueur minimale d'un mot du nom pour servir de temoin. */
    private const MIN_TOKEN_LENGTH = 4;

    public function search(string $query, int $maxResults = 5, ?string $subject = null): Collection
    {
        $pages = $this->call($query, $maxResults, self::PREFERRED_DOMAINS)
            ->concat($this->call($query, $maxResults))
            ->unique('url')
            ->values();

        return $this->rankBySubject($pages, $subject ?? $query);
    }

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
                'url'     => $r['url'],
                'title'   => $r['title'] ?? '',
                // raw_content n'est pas toujours renvoye ; content est alors
                // le seul texte disponible.
                'content' => filled($r['raw_content'] ?? null)
                    ? $r['raw_content']
                    : ($r['content'] ?? ''),
            ])
            ->filter(fn (array $r) => strlen($r['content']) > self::MIN_LENGTH)
            ->reject(fn (array $r) => $this->isBlocked($r['url']))
            ->values();
    }

    /**
     * Ecarte les pages qui ne nomment pas la personne, puis classe les
     * autres : domaines de reference d'abord, puis par nombre de mentions.
     */
    private function rankBySubject(Collection $pages, string $subject): Collection
    {
        $tokens = $this->nameTokens($subject);

        if ($tokens === []) {
            return $pages;
        }

        return $pages
            ->map(function (array $page) use ($tokens) {
                $haystack = mb_strtolower($page['content']." \n".$page['title']);

                $page['mentions'] = collect($tokens)
                    ->sum(fn (string $token) => mb_substr_count($haystack, $token));

                $page['preferred'] = $this->isPreferred($page['url']);

                return $page;
            })
            ->filter(fn (array $page) => $page['mentions'] > 0)
            ->sortBy([
                ['preferred', 'desc'],
                ['mentions', 'desc'],
            ])
            ->values();
    }

    /**
     * Les mots du nom assez longs pour servir de temoin.
     *
     * "Fatima Mernissi" donne ["fatima", "mernissi"] ; les particules
     * comme "el", "ben" ou "de" sont trop courantes pour distinguer
     * quoi que ce soit et sont ecartees.
     */
    private function nameTokens(string $subject): array
    {
        return collect(preg_split('/\s+/u', mb_strtolower(trim($subject))))
            ->filter(fn (string $token) => mb_strlen($token) >= self::MIN_TOKEN_LENGTH)
            ->values()
            ->all();
    }

    private function isPreferred(string $url): bool
    {
        foreach (self::PREFERRED_DOMAINS as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        return false;
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