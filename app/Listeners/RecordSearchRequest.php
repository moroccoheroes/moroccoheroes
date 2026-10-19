<?php

namespace App\Listeners;

use App\Events\HeroNotFound;
use App\Models\SearchRequest;

/**
 * Consigne une recherche infructueuse dans la file des demandes.
 *
 * Ce listener reste volontairement synchrone : il execute deux requetes
 * courtes, bien en deca de ce qui justifierait une file d'attente et un
 * worker a surveiller. Un listener plus lourd, comme l'envoi d'un
 * courriel, implementerait ShouldQueue.
 */
class RecordSearchRequest
{
    /** En deca, c'est une faute de frappe, pas une demande. */
    private const MIN_QUERY_LENGTH = 3;

    public function handle(HeroNotFound $event): void
    {
        $query = trim($event->query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return;
        }

        SearchRequest::record($query, $event->userId, $event->locale);
    }
}