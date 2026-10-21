<?php

namespace App\Console\Commands;

use App\Models\SearchRequest;
use Illuminate\Console\Command;

/**
 * Affiche la file des recherches restees sans resultat.
 *
 * Version terminal de l'ecran d'administration a venir. Les deux
 * s'appuieront sur les memes scopes du modele : ce qui est decide ici
 * sur l'ordre et le filtrage n'aura pas a etre reecrit cote React.
 */
class ListSearchRequests extends Command
{
    protected $signature = 'hero:requests
                            {--status=pending : pending, processing, resolved ou rejected}
                            {--limit=15 : Nombre de lignes affichees}';

    protected $description = 'Liste les recherches sans resultat, les plus demandees en premier';

    private const STATUSES = ['pending', 'processing', 'resolved', 'rejected'];

    public function handle(): int
    {
        $status = $this->option('status');

        if (! in_array($status, self::STATUSES, true)) {
            $this->components->error(
                "Statut inconnu : {$status}. Valeurs possibles : ".implode(', ', self::STATUSES)
            );

            return self::FAILURE;
        }

        $total = SearchRequest::where('status', $status)->count();

        $requests = SearchRequest::where('status', $status)
            ->popular()
            ->limit((int) $this->option('limit'))
            ->get();

        if ($requests->isEmpty()) {
            $this->components->info("Aucune demande au statut « {$status} ».");

            return self::SUCCESS;
        }

        $this->line('');
        $this->components->twoColumnDetail(
            "<fg=green>Demandes — {$status}</>",
            $requests->count().' affichees sur '.$total,
        );
        $this->line('');

        foreach ($requests as $request) {
            $this->components->twoColumnDetail(
                "#{$request->id}  {$request->query}",
                $request->hits_count.'x   '.($request->last_searched_at?->diffForHumans() ?? '—'),
            );
        }

        $this->line('');

        if ($status === 'pending') {
            $top = $requests->first();

            $this->components->info('Traiter la plus demandee :');
            $this->line("  php artisan hero:search \"{$top->query}\"");
            $this->line('');
        }

        return self::SUCCESS;
    }
}