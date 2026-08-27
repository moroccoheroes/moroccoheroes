# MoroccoHeroes

Moteur de recherche sur les figures marocaines — sport, culture, histoire, savoir.
Projet de stage · Ministère de la Culture et des Sports.

**Stack :** Laravel 13 · Inertia · React · Tailwind · SQLite
**Langues :** Français · العربية · ⵜⴰⵎⴰⵣⵉⵖⵜ · English

---

## 1. Principe

L'utilisateur n'est pas limité à un catalogue pré-rempli : il peut chercher
n'importe quelle figure marocaine. Le site fonctionne en **cache-first**.

```
Requête
  │
  ├─ Fiche déjà en base ?  ──oui──▶  affichage immédiat (~20 ms)
  │
  └─ non
       ├─ recherche web (Tavily)
       ├─ récupération des pages en parallèle (Http::pool)
       ├─ structuration par LLM (Gemini) → fiche JSON
       ├─ enregistrement en base, statut « brouillon »
       └─ affichage avec mention « générée automatiquement, non vérifiée »
```

La base se remplit donc à l'usage. Une même recherche ne coûte un appel externe
qu'une seule fois ; ensuite elle est servie localement, en quatre langues.

Les fiches issues de la génération automatique portent `is_ai_generated = true`
et restent en brouillon jusqu'à relecture par un modérateur. Chaque fiche cite
les sources d'où elle provient.

---

## 2. Installation

Prérequis : PHP 8.4 (via [Laravel Herd](https://herd.laravel.com)), Node 20+, Composer.

```bash
git clone https://github.com/<ORGANISATION>/moroccoheroes.git
cd moroccoheroes

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

herd link
npm run dev
```

Le site est servi sur `http://moroccoheroes.test`.

> `herd link` agit sur le dossier courant ; l'argument optionnel change le
> sous-domaine, pas le chemin.

### Configuration

```env
DB_CONNECTION=sqlite

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

TAVILY_API_KEY=
GEMINI_API_KEY=
```

Les deux services ont un palier gratuit suffisant pour le développement
(Tavily : 1 000 requêtes/mois ; Gemini : quota quotidien gratuit).

SQLite en développement : un seul fichier, zéro configuration. Les migrations
sont écrites avec le Schema Builder et fonctionnent aussi sur MySQL et
PostgreSQL sans modification.

---

## 3. Le multilingue

Le texte ne vit pas dans les tables principales mais dans des tables de
traduction dédiées (`hero_translations`, `category_translations`), une ligne par
langue.

Une colonne JSON `{"ar": "…", "fr": "…"}` aurait été plus rapide à écrire, mais
elle interdit de trier ou d'indexer par langue, et chaque langue ajoutée devient
une migration. Avec une table séparée, ajouter le tamazight n'a demandé que des
lignes supplémentaires dans le seeder.

Le trait `app/Concerns/HasTranslations.php` fournit `tr('champ')` avec un
**fallback champ par champ** : si la fiche amazighe existe mais que sa
biographie est vide, la biographie française s'affiche plutôt qu'un blanc. C'est
ce qui permet de publier des traductions partielles sans casser le site.

Les langues sont déclarées dans `config/locales.php`. Le middleware `SetLocale`
lit la langue en session et passe **avant** `HandleInertiaRequests`, qui la
partage avec React.

Le tamazight s'écrit en tifinagh (`zgh`), de gauche à droite, avec la police
*Noto Sans Tifinagh*. Les traductions amazighes sont en cours de validation
terminologique.

---

## 4. Schéma de données

| Table | Rôle |
|---|---|
| `categories` + `category_translations` | arbre à 2 niveaux (Sport → Football) |
| `heroes` | données non traduisibles : dates, statut, provenance, compteurs |
| `hero_translations` | tout le texte, une ligne par langue |
| `achievements` | palmarès (titres, records, œuvres) |
| `timeline_events` | frise chronologique |
| `media` / `sources` | images et références, avec licence et fiabilité |
| `tags` + `hero_tag` | étiquettes transversales |
| `hero_relations` | mentor, rival, coéquipier, famille |
| `hero_submissions` | fiches en attente de modération |
| `hero_chunks` | fragments indexés pour la recherche sémantique |
| `chat_sessions` / `chat_messages` | conversations avec l'agent, avec citations |
| `favorites` | héros mis de côté par un utilisateur |

---

## 5. Garde-fous

Une fiche générée automatiquement n'est jamais publiée telle quelle : elle est
créée en brouillon, signalée comme non vérifiée à l'affichage, et doit être
relue avant d'être publiée. Chaque fiche cite ses sources.

L'agent conversationnel est désactivé par défaut (`ai_chat_enabled = false`) et
s'active fiche par fiche. Pour une personne vivante, `ai_chat_mode` reste
`biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour*
elle. La règle est portée par le schéma, pas seulement par le prompt.

---

## 6. Feuille de route

- [x] **Sprint 1** — Schéma de données, models, seeders, socle multilingue
- [x] **Sprint 2** — Configuration multilingue, tamazight, switcher de langue
- [ ] **Sprint 3** — Moteur de recherche : web search, extraction LLM, persistance
- [ ] **Sprint 4** — Front public : recherche, fiche héros, i18n de l'interface, RTL
- [ ] **Sprint 5** — Déploiement (Laravel Forge) et intégration continue
- [ ] **Sprint 6** — Recherche sémantique sur le corpus accumulé
- [ ] **Sprint 7** — Modération des fiches générées
- [ ] **Sprint 8** — Agent conversationnel sourcé, documentation

---

## 7. Commandes utiles

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=CategorySeeder
php artisan tinker
npm run dev
npm run build
```

Vérifier le multilingue :

```php
$c = App\Models\Category::where('slug','sport')->with('translations')->first();
app()->setLocale('zgh');
$c->tr('name');   // ⴰⴷⴷⴰⵍ
```

---

## 8. Structure

```
app/
  Concerns/HasTranslations.php    trait de traduction
  Http/Middleware/SetLocale.php   résolution de la langue
  Models/                         Hero, Category, Achievement, …
  Services/                       recherche web, extraction, orchestration
config/locales.php                langues supportées
database/migrations/              migrations métier
database/seeders/                 catégories et fiches de démonstration
resources/js/                     application React (Inertia)
```
