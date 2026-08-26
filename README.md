# MoroccoHeroes — base de données

Sprint 2 : migrations, models Eloquent et seeders.

## 1. Créer le projet

```bash
laravel new moroccoheroes --react   # Laravel + Inertia + React + Tailwind
cd moroccoheroes
```

Dans Laravel Herd : `herd link` depuis le dossier du projet, le site est servi sur `http://moroccoheroes.test`.

## 2. Base de données

SQLite en développement : un seul fichier, zéro configuration. Dans `.env` :

```env
DB_CONNECTION=sqlite

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
```

```bash
touch database/database.sqlite
php artisan migrate --seed
```

Les migrations sont écrites avec le Schema Builder de Laravel et fonctionnent sur SQLite, MySQL et PostgreSQL sans modification. La migration `101700_add_vector_column` se saute d'elle-même hors PostgreSQL.

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
composer require laravel/scout
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
| `hero_chunks` | fragments indexés pour le RAG |
| `chat_sessions` / `chat_messages` | conversations avec l'agent IA, avec citations |
| `favorites` | héros mis de côté par un utilisateur |

## 6. Trois choix à connaître pour la soutenance

**Traductions en table séparée, pas en JSON.** Une colonne JSON `{"ar": "...", "fr": "..."}` est plus rapide à écrire, mais impossible à trier ou indexer par langue, et chaque langue ajoutée devient une migration. Avec `hero_translations`, ajouter le tamazight n'a demandé que des lignes supplémentaires dans le seeder.

**Quatre langues, dont le tamazight en Tifinagh** (`zgh`, de gauche à droite, police *Noto Sans Tifinagh*). Le fallback se fait **champ par champ** : si la fiche amazighe existe mais que sa biographie est vide, la biographie française s'affiche plutôt qu'un blanc. C'est ce qui permet de publier des traductions partielles sans casser le site. Les traductions amazighes sont en cours de validation terminologique.

**Le chat IA est désactivé par défaut** (`ai_chat_enabled = false`). Un modérateur l'active fiche par fiche, et seulement si les sources sont vérifiées. Pour une personne vivante, `ai_chat_mode` reste `biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour* elle. La règle est portée par le schéma, pas seulement par le prompt.

## 7. Suite

- Sprint 3 : back-office (Laravel + React + Inertia)
- Sprint 4 : front public (liste, filtres, fiche héros)
- Sprint 5 : service Python — recherche hybride (BM25 + embeddings)
- Sprint 6 : agent conversationnel sourcé
- Déploiement : Laravel Forge
