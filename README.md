# MoroccoHeroes — base de données

Sprint 2 : migrations, models Eloquent et seeders.

## 1. Créer le projet

```bash
laravel new moroccoheroes --react   # Laravel + Inertia + React + Tailwind
cd moroccoheroes
```

Dans Laravel Herd : le dossier est servi automatiquement sur `http://moroccoheroes.test`.

## 2. Base de données

PostgreSQL est recommandé (pgvector pour la recherche sémantique). Dans `.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=moroccoheroes
DB_USERNAME=postgres
DB_PASSWORD=

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
```

Si tu restes sur MySQL au début : la migration `101700_add_vector_column` se saute toute seule, et les embeddings restent en JSON. Ça marche jusqu'à ~500 héros.

## 3. Copier les fichiers

```
database/migrations/*   →  database/migrations/
database/seeders/*      →  database/seeders/
app/Models/*            →  app/Models/
app/Concerns/*          →  app/Concerns/
```

Le fichier `app/Concerns/HasTranslations.php` est un trait maison — pas besoin de package externe.

## 4. Dépendances

```bash
composer require laravel/scout laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate --seed
```

Vérifier ensuite :

```bash
php artisan tinker
>>> App\Models\Hero::published()->withTranslation('ar')->get()->map->tr('name');
```

## 5. Structure

| Table | Rôle |
|---|---|
| `categories` + `category_translations` | arbre à 2 niveaux (Sport → Football) |
| `heroes` | données non traduisibles : dates, statut, compteurs |
| `hero_translations` | tout le texte, une ligne par langue |
| `achievements` | palmarès (titres, records, œuvres) |
| `timeline_events` | frise chronologique |
| `media` / `sources` | images et références, avec licence et fiabilité |
| `tags` + `hero_tag` | étiquettes transversales |
| `hero_relations` | mentor, rival, coéquipier, famille |
| `hero_submissions` | contributions du public en attente de modération |
| `hero_chunks` | fragments indexés pour le RAG (+ `embedding_vector` sous Postgres) |
| `chat_sessions` / `chat_messages` | conversations avec l'agent IA, avec citations |
| `favorites` | héros mis de côté par un utilisateur |

## 6. Deux choix à connaître pour la soutenance

**Traductions en table séparée, pas en JSON.** Une colonne JSON `{"ar": "...", "fr": "..."}` est plus rapide à écrire, mais impossible à trier ou indexer par langue. Avec `hero_translations` on peut faire `ORDER BY name` en arabe, et Meilisearch indexe chaque langue proprement.

**Le chat IA est désactivé par défaut** (`ai_chat_enabled = false`). Un modérateur l'active fiche par fiche, et seulement si les sources sont vérifiées. Pour une personne vivante, `ai_chat_mode` reste `biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour* elle. C'est la garantie qui rend le projet défendable devant un ministère.

## 7. Suite

- Sprint 3 :  Back-office (Laravel (React + Inertia) )
- Sprint 4 : front public (liste, filtres, fiche héros)
- Sprint 5 : Scout + Meilisearch, puis recherche sémantique sur `hero_chunks`
