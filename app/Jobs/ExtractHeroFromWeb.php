<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use App\Services\HeroResearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Lance une recherche documentaire en file d'attente.
 *
 * Le pipeline demande une minute : trop pour une requete HTTP, que le
 * navigateur abandonne bien avant. L'ecran d'administration se contente
 * donc de deposer ce job et de rendre la main ; le worker fait le reste.
 *
 * Une seule tentative : reprendre automatiquement une recherche qui a
 * echoue consommerait des appels payants sans savoir pourquoi elle a
 * echoue. L'administrateur relance lui-meme quand il a vu la raison.
 */
class ExtractHeroFromWeb implements ShouldQueue
{
    use Queueable;

    /** Le pipeline enchaine deux appels au modele, dont un avec reprises. */
    public int $timeout = 300;

    public int $tries = 2;

    public int $backoff = 120;

    public function __construct(
        public string $name,
        public ?string $context = null,
        public ?int $searchRequestId = null,
    ) {}

    public function handle(HeroResearchService $research): void
    {
        $request = $this->searchRequestId !== null
            ? SearchRequest::find($this->searchRequestId)
            : null;

        $request?->update(['status' => 'processing']);

        // Le service remet la demande a « resolved » lui-meme, via resolveFor().
        $research->research($this->name, $this->context);
    }

    /**
     * Appelee par Laravel quand le job a epuise ses tentatives.
     *
     * La demande retourne dans la file avec la raison de l'echec, pour
     * que l'administrateur sache s'il doit changer le contexte ou
     * attendre que le fournisseur reponde a nouveau.
     */
    public function failed(Throwable $exception): void
    {
        if ($this->searchRequestId === null) {
            return;
        }

        SearchRequest::where('id', $this->searchRequestId)->update([
            'status' => 'pending',
            'review_note' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }
}