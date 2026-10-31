<?php

namespace App\Services;

use App\Ai\Agents\BiographyWriter;
use App\Ai\Agents\HeroExtractor;
use App\Models\Hero;
use App\Models\SearchRequest;
use Closure;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Recherche documentaire sur une figure, de la requete web a la fiche.
 *
 * Le service ignore qui l'appelle : une commande qui dessine dans un
 * terminal, un job qui tourne en file d'attente. Il se contente de
 * signaler ou il en est a travers $onProgress, que l'appelant fournit
 * ou non. Separer le travail de son compte rendu evite d'avoir deux
 * copies du pipeline qui divergeront a la premiere correction.
 *
 * La verification « la fiche existe deja » reste en dehors : c'est une
 * politique, pas du travail, et elle differe selon l'appelant.
 */
class HeroResearchService
{
    /** Longueur minimale d'une page nettoyee pour etre transmise au modele. */
    private const MIN_USABLE_LENGTH = 400;

    /** Nombre de sources transmises au modele. Au-dela, on paie de la redondance. */
    private const MAX_SOURCES = 4;

    /** Plafond du contexte, en caracteres : les modeles ont une limite de jetons. */
    private const MAX_CONTEXT_LENGTH = 18000;

    /** Pause entre les deux appels au modele, pour rester sous la limite de debit. */
    private const THROTTLE_SECONDS = 5;

    /** Contexte transmis au redacteur de biographie, plus court que celui de l'extraction. */
    private const BIO_CONTEXT_LENGTH = 6000;

    /** Attentes successives en cas de limitation de debit, en secondes. */
    private const BIO_RETRY_DELAYS = [0, 15, 30];

    public function __construct(
        private readonly WebSearchService $search,
        private readonly ContentCleaner $cleaner,
        private readonly HeroPersister $persister,
    ) {}

    /**
     * @param  Closure(string, array):void|null  $onProgress
     *
     * @throws RuntimeException quand aucune source exploitable n'est trouvee
     */
    public function research(string $name, ?string $context = null, ?Closure $onProgress = null): Hero
    {
        // Un rapporteur muet par defaut : le reste du service appelle
        // sans se demander si l'appelant en voulait un.
        $report = $onProgress ?? static fn (string $stage, array $data) => null;

        $query = trim($name.' '.($context ?? 'personnalite marocaine'));

        $pages = $this->fetchPages($query, $name, $report);
        $usable = $this->keepUsable($pages, $report);
        $contextText = $this->buildContext($usable, $report);

        $data = $this->extract($name, $contextText, $report);
        $data = $this->addBiography($data, $name, $contextText, $report);

        // Le modele peut citer une source qui ne lui a pas ete transmise.
        $data['sources'] = array_values(array_intersect(
            $data['sources'] ?? [],
            $usable->pluck('url')->all(),
        ));

        $hero = $this->persister->save($data);

        $report('saved', ['hero' => $hero]);

        $resolved = SearchRequest::resolveFor($name, $hero);

        if ($resolved !== null) {
            $report('resolved', ['request' => $resolved]);
        }

        return $hero;
    }

    private function fetchPages(string $query, string $name, Closure $report): Collection
    {
        $start = microtime(true);

        $pages = $this->search->search($query, 10, $name);

        $report('search', [
            'query' => $query,
            'ms' => $this->elapsed($start),
            'pages' => $pages->map(fn (array $page) => [
                'url' => $page['url'],
                'length' => strlen($page['content']),
            ])->all(),
        ]);

        if ($pages->isEmpty()) {
            throw new RuntimeException('Aucune source exploitable.');
        }

        return $pages;
    }

    private function keepUsable(Collection $pages, Closure $report): Collection
    {
        $usable = $pages
            ->map(fn (array $page) => [
                'url' => $page['url'],
                'content' => $this->cleaner->clean($page['content']),
            ])
            ->filter(fn (array $page) => strlen($page['content']) > self::MIN_USABLE_LENGTH)
            ->take(self::MAX_SOURCES)
            ->values();

        $report('filtered', [
            'used' => $usable->count(),
            'found' => $pages->count(),
        ]);

        if ($usable->isEmpty()) {
            throw new RuntimeException('Aucune source ne contient de texte exploitable.');
        }

        return $usable;
    }

    private function buildContext(Collection $usable, Closure $report): string
    {
        $context = $usable
            ->map(fn (array $page) => "SOURCE: {$page['url']}\n\n{$page['content']}")
            ->implode("\n\n---\n\n");

        $truncated = mb_strlen($context) > self::MAX_CONTEXT_LENGTH;

        if ($truncated) {
            $context = mb_substr($context, 0, self::MAX_CONTEXT_LENGTH);
        }

        $report('context', [
            'length' => mb_strlen($context),
            'truncated' => $truncated,
        ]);

        return $context;
    }

    private function extract(string $name, string $context, Closure $report): array
    {
        $start = microtime(true);

        $response = (new HeroExtractor)->prompt(
            "FIGURE RECHERCHEE : {$name}\n\nSOURCES :\n\n{$context}"
        );

        // Le SDK decode lui-meme la reponse quand un schema est defini.
        $data = $response->structured;

        if (! is_array($data)) {
            throw new RuntimeException('Reponse du modele illisible.');
        }

        $report('extracted', ['ms' => $this->elapsed($start)]);

        return $data;
    }

    /**
     * Une fiche sans biographie reste exploitable : l'echec est signale,
     * il n'interrompt pas la recherche.
     */
    private function addBiography(array $data, string $name, string $context, Closure $report): array
    {
        // L'extraction vient de consommer plusieurs milliers de jetons ;
        // enchainer immediatement declenche la limite de debit.
        sleep(self::THROTTLE_SECONDS);

        $start = microtime(true);
        $bioContext = mb_substr($context, 0, self::BIO_CONTEXT_LENGTH);
        $bio = null;
        $error = null;

        foreach (self::BIO_RETRY_DELAYS as $wait) {
            if ($wait > 0) {
                sleep($wait);
            }

            try {
                $response = (new BiographyWriter)->prompt(
                    "FIGURE : {$name}\n\nSOURCES :\n\n{$bioContext}"
                );

                $bio = $response->structured;

                if (is_array($bio) && filled($bio['fr'] ?? null)) {
                    break;
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();

                // Reessayer n'a de sens que sur une limitation de debit.
                if (! str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    break;
                }
            }
        }

        foreach (['fr', 'ar', 'en'] as $locale) {
            if (filled($bio[$locale] ?? null)) {
                $data['translations'][$locale]['biography'] = $bio[$locale];
            }
        }

        $report('biography', [
            'ok' => filled($bio['fr'] ?? null),
            'ms' => $this->elapsed($start),
            'error' => $error,
        ]);

        return $data;
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}