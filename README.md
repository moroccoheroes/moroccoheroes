# MoroccoHeroes

Moteur de recherche sur les figures marocaines — sport, culture, histoire, savoir.
Projet de stage · Ministère de la Culture et des Sports.

**Stack :** Laravel 13 · Inertia · React · Tailwind · SQLite · Laravel AI SDK
**Langues :** Français · العربية · ⵜⴰⵎⴰⵣⵉⵖⵜ · English

---

## 1. Principe

L'utilisateur cherche **dans la base locale**. Il ne voit que des fiches relues
et publiées, servies en quatre langues.

Quand une recherche ne trouve rien, aucun service externe n'est appelé : la
demande est enregistrée et remonte à l'administrateur, qui lance la recherche
documentaire, relit la fiche produite, puis la publie. La demande sort alors de
la file d'elle-même.

```
Visiteur                              Administrateur
   │                                        │
Recherche sans résultat                hero:requests
   │                                        │   la file, triée par demande
HeroNotFound (événement)                    │
   │                                   hero:search
RecordSearchRequest (listener)              │   recherche, extraction, brouillon
   │                                        │
   └──────▶  search_requests  ◀─────────────┘
             normalize() regroupe        resolveFor()
             hits_count +1               statut resolved + hero_id
```

Les deux côtés ne s'appellent jamais directement : la table est leur seul point
de contact. Le front public se contentera de signaler l'échec, l'écran
d'administration de lire la file.

Aucun contenu généré automatiquement n'est visible sans validation humaine.

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
AI_PROVIDER=groq
GROQ_MODEL=openai/gpt-oss-120b
```

Les deux services ont un palier gratuit suffisant pour le développement.

Le fournisseur de modèle passe par le **Laravel AI SDK** et se change dans la
configuration, sans toucher au code applicatif. Ce choix vient d'un incident
réel : un premier fournisseur a coupé l'accès en cours de développement et tout
l'appel avait dû être réécrit.

---

## 3. Le moteur d'extraction

```bash
php artisan hero:search "Nezha Bidouane" --context="athlete 400m haies" [--force]
```

| # | Étape | Rôle |
|---|---|---|
| 0 | Fiche existante ? | s'arrête, sauf `--force` |
| 1 | `WebSearchService` | recherche web, deux passages, tri par pertinence |
| 2 | Filtre — pages vides | réseaux sociaux, banques d'images, forums |
| 3 | `ContentCleaner` | 110 000 → 6 000 caractères |
| 4 | Filtre — après nettoyage | ce qui reste réellement exploitable |
| 5 | `HeroExtractor` | fiche structurée, schéma strict |
| 6 | `BiographyWriter` | biographie longue, trois langues |
| 7 | Filtre — sources citées | seulement celles transmises au modèle |
| 8 | `HeroPersister` | quatre tables, transaction, `status = draft` |
| 9 | `resolveFor()` | ferme la demande correspondante |

### Ne pas faire confiance au moteur de recherche

Restreindre la recherche aux domaines de référence paraissait suffisant. Ce ne
l'est pas : quand la figure ne s'y trouve pas sous cette orthographe exacte, le
moteur ne renvoie pas moins de résultats, il en renvoie de hors sujet. Une
recherche sur une sociologue a ainsi ramené la page d'accueil de Britannica et
deux articles sur les Jeux Olympiques, et la fiche produite était vide.

Les deux passages — domaines de référence puis web ouvert — sont donc
systématiques, et leurs résultats passent par un contrôle de pertinence : une
page doit nommer la personne pour être retenue, et plus elle la nomme, mieux
elle est classée.

### Trois filtres, trois problèmes distincts

Le premier travaille sur le HTML brut : certains sites bloquent les robots et
renvoient zéro caractère. Le deuxième mesure ce qui subsiste **après**
nettoyage : une page de fédération pesait 16 000 caractères et n'était faite que
d'images encodées. Le troisième restreint les sources citées à celles réellement
transmises — sur un site institutionnel, « d'où vient cette information » n'est
pas un détail.

### Deux agents plutôt qu'un

Demander en une seule réponse quatre langues, un palmarès, des sources **et**
une biographie de plusieurs paragraphes conduit le modèle à sacrifier le champ
le plus coûteux : la biographie ressortait systématiquement `null`, quel que
soit le modèle et quelles que soient les instructions.

Séparer en deux appels donne à chacun une tâche et une seule. Le second reçoit
un contexte plus court et réessaie avec une attente croissante en cas de
limitation de débit. Si la biographie échoue malgré tout, la commande avertit et
continue : une fiche sans biographie reste exploitable.

---

## 4. La file des demandes

```bash
php artisan hero:requests [--status=pending] [--limit=15]
```

```
  Demandes — pending                              3 affichées sur 3

  #1  Fatima Mernissi                             5x   il y a 18 minutes
  #3  Leila Abouzeid                              2x   il y a 17 minutes
  #4  Touria Chaoui                               1x   il y a 17 minutes

  Traiter la plus demandée :
  php artisan hero:search "Fatima Mernissi"
```

### Un événement, pas un appel direct

Le code qui constate l'échec se contente de le signaler :

```php
HeroNotFound::dispatch($query, auth()->id());
```

Il ignore ce qui en sera fait. Aujourd'hui un listener enregistre la demande ;
en ajouter un second — prévenir un modérateur, alimenter des statistiques — ne
demandera pas de toucher au code qui déclenche la recherche.

Ce listener reste synchrone : deux requêtes courtes ne justifient pas une file
d'attente et un worker à surveiller. Un listener plus lourd, comme un envoi de
courriel, implémenterait `ShouldQueue`.

### Une ligne par demande, pas par recherche

`SearchRequest::normalize()` ramène les variantes à une clé unique : minuscules,
espaces normalisés, signes diacritiques retirés. « Said Aouita », « said aouita »
et « SAÏD AOUITA » comptent pour une seule ligne, dont le compteur donne à
l'administrateur son ordre de travail.

La normalisation ne translittère pas. `Str::ascii()` aurait transformé « Saïd »
en « Said », mais aurait vidé tout texte arabe — et avec une contrainte d'unicité
sur la colonne, **toutes les requêtes arabes se seraient confondues en une seule
ligne**. La décomposition Unicode suivie du retrait des marques non espaçantes
retire les accents latins et les harakat arabes sans toucher aux lettres.

La même normalisation sert au rapprochement : l'administrateur peut lancer la
recherche avec une casse différente de ce qu'avait tapé le visiteur, les deux
retombent sur la même clé et la demande se ferme.

Restent à traiter les variantes proprement arabes — hamza, ta marbouta — qui
demandent des règles dédiées et attendent la recherche sémantique.

---

## 5. Le multilingue

Le texte vit dans des tables dédiées (`hero_translations`,
`category_translations`), une ligne par langue. Une colonne JSON aurait été plus
rapide à écrire mais interdit de trier ou d'indexer par langue, et chaque langue
ajoutée deviendrait une migration.

Le trait `app/Concerns/HasTranslations.php` fournit `tr('champ')` avec un
**fallback champ par champ** : si la fiche amazighe existe mais que sa
biographie est vide, la biographie française s'affiche plutôt qu'un blanc.

`HeroPersister` n'écrit jamais une ligne de traduction entièrement vide. Le
modèle renvoie `zgh` avec tous ses champs à `null` ; insérer cette ligne
casserait le fallback, qui la trouverait et renverrait `null` sans jamais
essayer le français.

Le tamazight s'écrit en tifinagh (`zgh`), de gauche à droite, police
*Noto Sans Tifinagh*. L'extraction laisse ces champs vides : la transcription
des noms propres en tifinagh n'a pas de norme stable et ne doit pas être devinée
par un modèle. Elle est saisie à la main après validation.

---

## 6. Schéma de données

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
| `search_requests` | recherches sans résultat, regroupées et comptées |
| `hero_submissions` | fiches en attente de modération |
| `hero_chunks` | fragments indexés pour la recherche sémantique |
| `chat_sessions` / `chat_messages` | conversations avec l'agent, avec citations |
| `favorites` | héros mis de côté par un utilisateur |

### Idempotence

`hero:search` peut être relancée sur une fiche existante pour l'enrichir. Chaque
méthode d'enregistrement gère la reprise à sa manière : `updateOrCreate` sur la
locale pour les traductions, suppression puis recréation pour le palmarès,
suppression sélective pour les sources — afin qu'une source déjà validée par un
modérateur conserve son statut.

---

## 7. Garde-fous

`status = 'draft'` est écrit en dur, pas lu depuis la réponse du modèle. Chaque
fiche cite ses sources, avec `is_verified = false` tant qu'un humain ne les a pas
contrôlées.

Les instructions imposent de ne rien inventer et de mettre `null` en cas de
doute. Cette règle tient : sur une recherche dont les sources ne contenaient
aucune information biographique, le modèle a renvoyé un palmarès vide plutôt que
de le combler.

L'agent conversationnel est désactivé par défaut (`ai_chat_enabled = false`) et
s'active fiche par fiche. Pour une personne vivante, `ai_chat_mode` reste
`biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour*
elle. La règle est portée par le schéma, pas seulement par le prompt.

---

## 8. Feuille de route

- [x] **Sprint 1** — Schéma de données, models, seeders, socle multilingue
- [x] **Sprint 2** — Configuration multilingue, tamazight, switcher de langue
- [x] **Sprint 3** — Moteur d'extraction : `hero:search`, agents, persistance
- [x] **Sprint 4** — File des demandes : événement, listener, résolution
- [ ] **Sprint 5** — Espace administrateur : demandes, brouillons, publication
- [ ] **Sprint 6** — Front public : recherche, fiche héros, i18n, RTL
- [ ] **Sprint 7** — Déploiement (Laravel Forge) et intégration continue
- [ ] **Sprint 8** — Recherche sémantique et agent conversationnel sourcé

---

## 9. Commandes utiles

```bash
php artisan hero:search "Nawal El Moutawakel" --context="athlete championne olympique"
php artisan hero:requests
php artisan hero:requests --status=resolved
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan tinker
npm run dev
```

Vérifier le multilingue :

```php
$c = App\Models\Category::where('slug','sport')->with('translations')->first();
app()->setLocale('zgh');
$c->tr('name');   // ⴰⴷⴷⴰⵍ
```

Vérifier le regroupement des demandes :

```php
App\Events\HeroNotFound::dispatch('Said Aouita');
App\Events\HeroNotFound::dispatch('  SAÏD  AOUITA ');
App\Models\SearchRequest::first()->hits_count;   // 2
```

---

## 10. Structure

```
app/
  Ai/Agents/
    HeroExtractor.php             fiche structurée (schéma strict)
    BiographyWriter.php           biographie longue, trois langues
  Concerns/HasTranslations.php    trait de traduction, fallback par champ
  Console/Commands/
    SearchHero.php                orchestration du pipeline
    ListSearchRequests.php        file des demandes
  Events/HeroNotFound.php         recherche restée sans résultat
  Listeners/RecordSearchRequest.php
  Http/Middleware/SetLocale.php   résolution de la langue
  Models/                         Hero, Category, SearchRequest, …
  Services/
    WebSearchService.php          recherche web, pertinence, premier filtrage
    ContentCleaner.php            réduction du HTML brut
    HeroPersister.php             écriture en base, en transaction
config/ai.php                     fournisseurs et modèles
config/locales.php                langues supportées
database/migrations/              migrations métier
database/seeders/                 catégories et fiches de démonstration
resources/js/                     application React (Inertia)
```
