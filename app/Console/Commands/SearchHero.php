<?php

namespace App\Console\Commands;

use App\Models\Hero;
use App\Models\SearchRequest;
use App\Services\HeroResearchService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Lance une recherche documentaire depuis le terminal.
 *
 * La commande ne fait plus le travail : elle le confie a
 * HeroResearchService et se charge de l'afficher. Le job de la file
 * d'attente appellera le meme service avec un autre rapporteur.
 *
 * Ce qui reste ici releve de la politique du terminal : refuser de
 * recommencer sans --force, et rendre compte a l'ecran.
 */
class SearchHero extends Command
{
    protected $signature = 'hero:search
                            {name : Nom de la figure a rechercher}
                            {--context= : Precision ajoutee a la requete web}
                            {--force : Relance extraction meme si la fiche existe}';

    protected $description = 'Recherche une figure marocaine et cree une fiche en brouillon';

    public function handle(HeroResearchService $research): int
    {
        $name = $this->argument('name');

        $existing = Hero::whereHas('translations', fn ($q) => $q->where('name', 'like', "%{$name}%"))
            ->first();

        if ($existing && ! $this->option('force')) {
            $this->components->warn("Fiche deja presente : {$existing->slug} ({$existing->status})");
            $this->components->info('Utilisez --force pour relancer extraction.');

            return self::SUCCESS;
        }

        $resolved = null;

        try {
            $hero = $research->research(
                $name,
                $this->option('context'),
                $this->reporter($resolved),
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->summary($hero, $resolved);

        return self::SUCCESS;
    }

    /**
     * Traduit les etapes du service en lignes de terminal.
     *
     * @param  SearchRequest|null  $resolved  rempli par reference, lu ensuite par summary()
     */
    private function reporter(?SearchRequest &$resolved): callable
    {
        return function (string $stage, array $data) use (&$resolved): void {
            match ($stage) {
                'search' => $this->reportSearch($data),
                'filtered' => $this->components->info(
                    "{$data['used']} source(s) retenue(s) sur {$data['found']}."
                ),
                'context' => $this->reportContext($data),
                'extracted' => $this->components->twoColumnDetail(
                    'Extraction', $this->seconds($data['ms'])
                ),
                'biography' => $this->reportBiography($data),
                'resolved' => $resolved = $data['request'],
                default => null,
            };
        };
    }

    private function reportSearch(array $data): void
    {
        $this->line('');
        $this->components->twoColumnDetail(
            "Recherche : {$data['query']}",
            $this->seconds($data['ms']),
        );
        $this->line('');

        foreach ($data['pages'] as $page) {
            $this->components->twoColumnDetail(
                parse_url($page['url'], PHP_URL_HOST),
                number_format($page['length']).' car.',
            );
        }

        $this->line('');
    }

    private function reportContext(array $data): void
    {
        if ($data['truncated']) {
            $this->components->warn('Contexte tronque au plafond.');
        }

        $this->components->info(
            'Contexte reduit a '.number_format($data['length']).' caracteres.'
        );
    }

    private function reportBiography(array $data): void
    {
        $this->components->twoColumnDetail(
            'Biographie',
            $data['ok'] ? $this->seconds($data['ms']) : '<fg=yellow>echec</>',
        );

        if (! $data['ok'] && $data['error'] !== null) {
            $this->components->warn(mb_substr($data['error'], 0, 200));
        }
    }

    private function summary(Hero $hero, ?SearchRequest $resolved): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=green>Fiche creee</>', $hero->slug);
        $this->components->twoColumnDetail('Statut', $hero->status);
        $this->components->twoColumnDetail('Langues', $hero->translations->pluck('locale')->implode(', '));
        $this->components->twoColumnDetail('Biographie', $hero->tr('biography', 'fr') ? 'oui' : '—');
        $this->components->twoColumnDetail('Palmares', (string) $hero->achievements->count());
        $this->components->twoColumnDetail('Sources', (string) $hero->sources->count());

        if ($resolved !== null) {
            $this->components->twoColumnDetail(
                'Demande traitee',
                "#{$resolved->id}  ({$resolved->hits_count} recherches)",
            );
        }

        $this->line('');
    }

    private function seconds(int $ms): string
    {
        return $ms < 1000
            ? $ms.' ms'
            : round($ms / 1000, 1).' s';
    }
}