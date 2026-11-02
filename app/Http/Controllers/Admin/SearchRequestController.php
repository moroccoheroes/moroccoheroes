<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractHeroFromWeb;
use App\Models\SearchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La file des recherches restees sans resultat.
 *
 * L'ordre et le filtrage viennent des scopes du modele, comme pour la
 * commande hero:requests : l'ecran ne fait que les afficher. Si la
 * notion de « plus demande » change un jour, elle change a un endroit.
 */
class SearchRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', 'pending')->toString();

        return Inertia::render('admin/requests/index', [
            'requests' => SearchRequest::with('hero')
                ->where('status', $status)
                ->popular()
                ->paginate(20)
                ->withQueryString(),

            'filters' => ['status' => $status],

            // Alimente les onglets : chaque statut avec son effectif.
            'counts' => SearchRequest::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    /**
     * Le pipeline demande une minute, bien plus qu'une requete HTTP ne
     * peut attendre : on depose un job et on rend la main aussitot.
     */
    public function run(Request $request, SearchRequest $searchRequest): RedirectResponse
    {
        $validated = $request->validate([
            'context' => ['nullable', 'string', 'max:200'],
        ]);

        ExtractHeroFromWeb::dispatch(
            $searchRequest->query,
            $validated['context'] ?? null,
            $searchRequest->id,
        );

        return back()->with('success', "Recherche lancee pour « {$searchRequest->query} ».");
    }
}