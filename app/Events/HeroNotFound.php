<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Une recherche n'a rien donne dans la base locale.
 *
 * L'evenement ne decide de rien : il constate. Ce sont les listeners
 * qui choisissent quoi en faire. Aujourd'hui l'un d'eux enregistre la
 * demande ; demain un autre pourra prevenir un moderateur ou alimenter
 * des statistiques, sans qu'on touche au code qui declenche la recherche.
 */
class HeroNotFound
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $query,
        public ?int $userId = null,
        public ?string $locale = null,
    ) {}
}