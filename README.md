# MoroccoHeroes

Moteur de recherche sur les figures marocaines — sport, culture, histoire, savoir.
Projet de stage · Ministère de la Culture et des Sports.

**Stack :** Laravel 13 · Inertia · React · Tailwind · SQLite
**Langues :** Français · العربية · ⵜⴰⵎⴰⵣⵉⵖⵜ · English

---

## 1. Principe

L'utilisateur cherche **dans la base locale**. Il ne voit que des fiches relues
et publiées, servies en quatre langues.

Quand une recherche ne trouve rien, elle n'appelle aucun service externe : elle
est **enregistrée comme demande** et remonte à l'administrateur. Celui-ci lance
la recherche documentaire depuis son espace, relit la fiche produite, puis la
publie.

```
Utilisateur
    │
    ▼
Recherche en base ──── trouvé ──▶ fiche publiée (~20 ms, 4 langues)
    │
 rien trouvé
    │
    ▼
Événement HeroNotFound ──▶ table search_requests
                                    │
                                    ▼
                        Espace administrateur
                                    │
                    php artisan hero:search "…"
                                    │
                    recherche web → extraction → brouillon
                                    │
                              relecture
                                    │
                                    ▼
                             fiche publiée
```

Les demandes identiques sont regroupées et comptées : l'administrateur traite
en priorité ce que les visiteurs cherchent le plus.

Aucun contenu généré automatiquement n'est visible sans validation humaine. Une
fiche publiée sur un site du Ministère engage l'institution : elle doit avoir
été relue.

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
GROQ_API_KEY=
```

Les deux services ont un palier gratuit suffisant pour le développement.

Le fournisseur de modèle passe par le **Laravel AI SDK** : il se change dans la
configuration, sans toucher au code applicatif. Ce choix vient d'un incident
réel — un fournisseur ayant coupé l'accès en cours de développement, tout
l'appel avait dû être réécrit. L'abstraction évite que cela se reproduise.

SQLite en développement : un seul fichier, zéro configuration. Les migrations
utilisent le Schema Builder et fonctionnent aussi sur MySQL et PostgreSQL.

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
*Noto Sans Tifinagh*. L'extraction automatique laisse volontairement les champs
amazighs vides : la transcription des noms propres en tifinagh n'a pas de norme
stable et ne doit pas être devinée par un modèle. Ces champs sont saisis
manuellement après validation terminologique.

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
| `search_requests` | recherches sans résultat, regroupées et comptées |
| `hero_submissions` | fiches en attente de modération |
| `hero_chunks` | fragments indexés pour la recherche sémantique |
| `chat_sessions` / `chat_messages` | conversations avec l'agent, avec citations |
| `favorites` | héros mis de côté par un utilisateur |

---

## 5. Le pipeline d'extraction

Lancé par l'administrateur via `php artisan hero:search "Nom"` :

1. **Recherche web** — cinq résultats avec le contenu brut des pages.
2. **Filtrage** — les pages vides sont écartées, les réseaux sociaux aussi : ce
   ne sont pas des sources acceptables pour un site institutionnel.
3. **Nettoyage** — suppression des images encodées, des liens, des sections de
   références et de navigation, puis troncature. En pratique, cette étape fait
   passer le contexte d'environ 100 000 à 13 000 caractères.
4. **Extraction** — un agent produit une fiche structurée en JSON, avec
   consigne stricte de ne rien inventer et de mettre `null` en cas de doute.
5. **Persistance** — fiche, traductions, palmarès et sources, en brouillon.

Une source propre vaut mieux qu'une source volumineuse : une fiche de
fédération de 7 000 caractères donne un meilleur résultat qu'un article
encyclopédique de 75 000, dont l'essentiel est constitué de tableaux et de
références.

---

## 6. Garde-fous

Aucune fiche générée n'est publiée telle quelle : brouillon, mention explicite
à l'affichage, relecture obligatoire. Chaque fiche cite ses sources.

L'agent conversationnel est désactivé par défaut (`ai_chat_enabled = false`) et
s'active fiche par fiche. Pour une personne vivante, `ai_chat_mode` reste
`biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour*
elle. La règle est portée par le schéma, pas seulement par le prompt : une fiche
non validée ne peut pas répondre, même si le prompt est contourné.

---

## 7. Feuille de route

- [x] **Sprint 1** — Schéma de données, models, seeders, socle multilingue
- [x] **Sprint 2** — Configuration multilingue, tamazight, switcher de langue
- [ ] **Sprint 3** — Commande `hero:search` : recherche web, extraction, persistance
- [ ] **Sprint 4** — Événement `HeroNotFound` et file des demandes
- [ ] **Sprint 5** — Espace administrateur : demandes, brouillons, édition, publication
- [ ] **Sprint 6** — Front public : recherche, fiche héros, i18n de l'interface, RTL
- [ ] **Sprint 7** — Déploiement (Laravel Forge) et intégration continue
- [ ] **Sprint 8** — Recherche sémantique et agent conversationnel sourcé

---

## 8. Commandes utiles

```bash
php artisan hero:search "Nawal El Moutawakel"
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

## 9. Structure

```
app/
  Ai/Agents/                      agents d'extraction (Laravel AI SDK)
  Concerns/HasTranslations.php    trait de traduction
  Console/Commands/               hero:search
  Events/ · Listeners/            HeroNotFound → file des demandes
  Http/Middleware/SetLocale.php   résolution de la langue
  Models/                         Hero, Category, Achievement, …
  Services/                       recherche web, nettoyage, orchestration
config/locales.php                langues supportées
database/migrations/              migrations métier
database/seeders/                 catégories et fiches de démonstration
resources/js/                     application React (Inertia)
```
