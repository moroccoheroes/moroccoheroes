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
 * Conduit une recherche documentaire de bout en bout.
 *
 * Toute la logique vit ici. La commande artisan et le job de file
 * d'attente ne sont que deux manieres de la declencher : le service
 * ignore laquelle l'appelle. Il rend compte de son avancement par un
 * closure optionnel, que l'appelant affiche, journalise, ou ignore.
 */
class HeroResearchService
{
    private const SEARCH_RESULTS = 10;

    /** Longueur minimale d'une page nettoyee pour etre transmise au modele. */
    private const MIN_USABLE_LENGTH = 400;

    /** Au-dela, on paie de la redondance plus que de l'information. */
    private const MAX_SOURCES = 4;

    /** Plafond du contexte : les modeles ont une limite de jetons. */
    private const MAX_CONTEXT_LENGTH = 18000;

    /** Pause entre les deux appels, pour rester sous la limite de debit. */
    private const THROTTLE_SECONDS = 5;

    private const BIO_CONTEXT_LENGTH = 6000;

    /** Attentes successives en cas de limitation de debit, en secondes. */
    private const BIO_RETRY_DELAYS = [0, 15, 30];

    public function __construct(
        private readonly WebSearchService $search,
        private readonly ContentCleaner $cleaner,
        private readonly HeroPersister $persister,
    ) {}

    /** La fiche existe-t-elle deja, sous une orthographe proche ? */
    public function findExisting(string $name): ?Hero
    {
        return Hero::whereHas('translations', fn ($q) => $q->where('name', 'like', "%{$name}%"))
            ->with('translations')
            ->first();
    }

    /**
     * @param  Closure(string, array): void|null  $onProgress
     *
     * @throws RuntimeException quand aucune source exploitable n'est trouvee
     */
    public function research(string $name, ?string $context = null, ?Closure $onProgress = null): Hero
    {
        $query = trim($name.' '.($context ?? 'personnalite marocaine'));

        // --- Recherche web ---

        $startedAt = microtime(true);
        $pages = $this->search->search($query, self::SEARCH_RESULTS, $name);

        $onProgress?->('search', [
            'query'   => $query,
            'seconds' => microtime(true) - $startedAt,
            'pages'   => $pages,
        ]);

        if ($pages->isEmpty()) {
            throw new RuntimeException("Aucune source exploitable pour « {$name} ».");
        }

        // --- Nettoyage et second filtrage ---

        $usable = $pages
            ->map(fn (array $page) => [
                'url'     => $page['url'],
                'content' => $this->cleaner->clean($page['content']),
            ])
            ->filter(fn (array $page) => strlen($page['content']) > self::MIN_USABLE_LENGTH)
            ->take(self::MAX_SOURCES)
            ->values();

        if ($usable->isEmpty()) {
            throw new RuntimeException("Aucune source ne contient de texte exploitable pour « {$name} ».");
        }

        $prompt = $usable
            ->map(fn (array $page) => "SOURCE: {$page['url']}\n\n{$page['content']}")
            ->implode("\n\n---\n\n");

        $truncated = mb_strlen($prompt) > self::MAX_CONTEXT_LENGTH;

        if ($truncated) {
            $prompt = mb_substr($prompt, 0, self::MAX_CONTEXT_LENGTH);
        }

        $onProgress?->('context', [
            'found'     => $pages->count(),
            'used'      => $usable->count(),
            'length'    => mb_strlen($prompt),
            'truncated' => $truncated,
        ]);

        // --- Extraction de la fiche ---

        $startedAt = microtime(true);
        $data = (new HeroExtractor)->prompt(
            "FIGURE RECHERCHEE : {$name}\n\nSOURCES :\n\n{$prompt}"
        )->structured;

        if (! is_array($data)) {
            throw new RuntimeException('Reponse du modele illisible.');
        }

        $onProgress?->('extraction', ['seconds' => microtime(true) - $startedAt]);

        // --- Biographie, appel separe ---

        // L'extraction vient de consommer plusieurs milliers de jetons ;
        // enchainer immediatement declenche la limite de debit.
        sleep(self::THROTTLE_SECONDS);

        $startedAt = microtime(true);
        $error = null;
        $bio = $this->writeBiography($name, $prompt, $error);

        foreach (['fr', 'ar', 'en'] as $locale) {
            if (filled($bio[$locale] ?? null)) {
                $data['translations'][$locale]['biography'] = $bio[$locale];
            }
        }

        $onProgress?->('biography', [
            'seconds' => microtime(true) - $startedAt,
            'written' => filled($bio['fr'] ?? null),
            'error'   => $error,
        ]);

        // --- Sources reellement transmises ---

        $data['sources'] = array_values(array_intersect(
            $data['sources'] ?? [],
            $usable->pluck('url')->all(),
        ));

        // --- Enregistrement ---

        $hero = $this->persister->save($data);

        $onProgress?->('saved', ['hero' => $hero]);

        // --- Fermeture de la demande, s'il y en avait une ---

        $resolved = SearchRequest::resolveFor($name, $hero);

        $onProgress?->('resolved', ['request' => $resolved]);

        return $hero;
    }

    /**
     * Redige la biographie, en reessayant quand le fournisseur limite le debit.
     *
     * Une biographie manquante ne fait pas echouer la recherche : la fiche
     * reste exploitable sans elle, et le moderateur pourra la relancer.
     */
    private function writeBiography(string $name, string $context, ?string &$error): array
    {
        $shortContext = mb_substr($context, 0, self::BIO_CONTEXT_LENGTH);

        foreach (self::BIO_RETRY_DELAYS as $wait) {
            if ($wait > 0) {
                sleep($wait);
            }

            try {
                $bio = (new BiographyWriter)->prompt(
                    "FIGURE : {$name}\n\nSOURCES :\n\n{$shortContext}"
                )->structured;

                if (is_array($bio) && filled($bio['fr'] ?? null)) {
                    return $bio;
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();

                // Reessayer n'a de sens que sur une limitation de debit.
                if (! str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    return [];
                }
            }
        }

        return [];
    }
}