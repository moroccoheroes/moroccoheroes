<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hero;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les fiches, cote administration.
 *
 * Volontairement minimal a ce stade : l'ecran complet (indicateurs de
 * langues, filtres, publication) arrive a l'etape 5, une fois qu'on
 * aura vu ce que le pipeline produit reellement.
 */
class HeroController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/heroes/index', [
            'heroes' => Hero::with('translations', 'category.translations')
                ->latest('updated_at')
                ->paginate(20),
        ]);
    }
}