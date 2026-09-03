<?php

namespace App\Console\Commands;

use App\Models\Hero;
use App\Ai\Agents\HeroExtractor;
use App\Services\ContentCleaner;
use App\Services\HeroPersister;
use App\Services\WebSearchService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Recherche une figure sur le web et en cree une fiche en brouillon.
 *
 * Cette commande n'est jamais declenchee par un visiteur : elle est
 * lancee par un administrateur, depuis le terminal ou depuis son
 * espace, pour repondre a une demande restee sans resultat.
 *
 * Le filtrage se fait en deux temps. WebSearchService ecarte les pages
 * vides et les domaines non recevables sur le HTML brut ; ici, apres
 * nettoyage, on ecarte celles dont il ne reste rien d'exploitable.
 * Une page peut peser 16 000 caracteres et n'etre faite que d'images.
 */
class SearchHero extends Command
{
    protected $signature = 'hero:search
                        {name : Nom de la figure a rechercher}
                        {--context= : Precision ajoutee a la requete web}
                        {--force : Relance l\'extraction meme si la fiche existe}';

    protected $description = 'Recherche une figure marocaine et cree une fiche en brouillon';

    /** Longueur minimale d'une page nettoyee pour etre transmise au modele. */
    private const MIN_USABLE_LENGTH = 800;

    public function handle(
        WebSearchService $search,
        ContentCleaner $cleaner,
        HeroPersister $persister,
    ): int {
        $name  = $this->argument('name');
        $query = trim($name.' '.($this->option('context') ?? 'personnalite marocaine'));

        $existing = Hero::whereHas('translations', fn ($q) => $q->where('name', 'like', "%{$name}%"))
    ->with('translations')
    ->first();

if ($existing && ! $this->option('force')) {
    $this->components->warn("Fiche deja presente : {$existing->slug} ({$existing->status})");
    $this->components->info('Utilisez --force pour relancer l\'extraction.');

    return self::SUCCESS;
}
        
        try {
            $pages = null;

            $this->components->task("Recherche web : {$query}", function () use ($search, $query, &$pages) {
                $pages = $search->search($query, 10);

                return $pages->isNotEmpty();
            });

            if ($pages->isEmpty()) {
                $this->components->error('Aucune source exploitable.');

                return self::FAILURE;
            }

            $this->line('');
            foreach ($pages as $page) {
                $this->components->twoColumnDetail(
                    parse_url($page['url'], PHP_URL_HOST),
                    number_format(strlen($page['content'])).' car.',
                );
            }
            $this->line('');

            $usable = $pages
                ->map(fn (array $p) => [
                    'url'     => $p['url'],
                    'content' => $cleaner->clean($p['content']),
                ])
                ->filter(fn (array $p) => strlen($p['content']) > self::MIN_USABLE_LENGTH)
                ->values();

            if ($usable->isEmpty()) {
                $this->components->error('Aucune source ne contient de texte exploitable.');

                return self::FAILURE;
            }

            $this->components->info(
                $usable->count().' source(s) retenue(s) sur '.$pages->count().'.'
            );

            $context = $usable
                ->map(fn (array $p) => "SOURCE: {$p['url']}\n\n{$p['content']}")
                ->implode("\n\n---\n\n");

            $this->components->info('Contexte reduit a '.number_format(strlen($context)).' caracteres.');

            $data = null;

            $this->components->task('Extraction', function () use ($name, $context, &$data) {
                $response = (new HeroExtractor)->prompt(
                    "FIGURE RECHERCHEE : {$name}\n\nSOURCES :\n\n{$context}"
                );

                $data = json_decode($response->text, true);

                return is_array($data);
            });

            if (! is_array($data)) {
                $this->components->error('Reponse du modele illisible.');

                return self::FAILURE;
            }

            // Le modele peut citer une source qui ne lui a pas ete transmise.
            $data['sources'] = array_values(array_intersect(
                $data['sources'] ?? [],
                $usable->pluck('url')->all(),
            ));

            $hero = null;

            $this->components->task('Enregistrement', function () use ($persister, $data, &$hero) {
                $hero = $persister->save($data);

                return true;
            });
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->components->twoColumnDetail('<fg=green>Fiche creee</>', $hero->slug);
        $this->components->twoColumnDetail('Statut', $hero->status);
        $this->components->twoColumnDetail('Langues', $hero->translations->pluck('locale')->implode(', '));
        $this->components->twoColumnDetail('Palmares', (string) $hero->achievements->count());
        $this->components->twoColumnDetail('Sources', (string) $hero->sources->count());
        $this->line('');

        return self::SUCCESS;
    }
}