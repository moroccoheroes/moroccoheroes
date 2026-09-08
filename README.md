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
documentaire depuis son espace, relit la fiche produite, puis la publie.

```
Utilisateur
    │
    ▼
Recherche en base ──── trouvé ──▶ fiche publiée (~20 ms, 4 langues)
    │
 rien trouvé
    │
    ▼
Demande enregistrée ──▶ administrateur ──▶ hero:search ──▶ brouillon ──▶ publication
```

Aucun contenu généré automatiquement n'est visible sans validation humaine. Une
fiche publiée sur un site du Ministère engage l'institution.

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

Huit étapes, une fiche en brouillon à l'arrivée.

| # | Étape | Rôle |
|---|---|---|
| 0 | Fiche existante ? | s'arrête, sauf `--force` |
| 1 | `WebSearchService` | Tavily, domaines de référence d'abord |
| 2 | Filtre 1 | pages vides, réseaux sociaux, banques d'images |
| 3 | `ContentCleaner` | 110 000 → 6 000 caractères |
| 4 | Filtre 2 | ce qui reste réellement après nettoyage |
| 5 | `HeroExtractor` | fiche structurée, schéma strict |
| 6 | `BiographyWriter` | biographie longue, trois langues |
| 7 | Filtre 3 | sources réellement transmises au modèle |
| 8 | `HeroPersister` | quatre tables, transaction, `status = draft` |

### Pourquoi trois filtres

Chacun répond à un problème différent, découvert en testant.

Le premier travaille sur le HTML brut : certains sites bloquent les robots et
renvoient zéro caractère. Le deuxième mesure ce qui subsiste **après**
nettoyage : une page de fédération pesait 16 000 caractères et n'était faite que
d'images encodées ; une banque d'images en pesait 75 000 pour zéro information,
et la fiche produite était vide. Le troisième restreint les sources citées à
celles effectivement transmises — sur un site institutionnel, « d'où vient cette
information » n'est pas un détail.

### Pourquoi deux agents

Demander en une seule réponse quatre langues, un palmarès, des sources **et**
une biographie de plusieurs paragraphes conduit le modèle à sacrifier le champ
le plus coûteux : la biographie ressortait systématiquement `null`, quel que
soit le modèle et quelles que soient les instructions.

Séparer en deux appels donne à chacun une tâche et une seule. Le second reçoit
un contexte volontairement plus court, et réessaie avec une attente croissante
en cas de limitation de débit. Si la biographie échoue malgré tout, la commande
avertit et continue : une fiche sans biographie reste exploitable.

### Une source propre vaut mieux qu'une source volumineuse

Une page de fondation de 7 000 caractères a donné une meilleure extraction qu'un
article encyclopédique de 75 000, dont l'essentiel était constitué de tableaux
et de références. Les domaines de référence sont donc interrogés en premier ;
la recherche ne s'ouvre au reste du web que si elle ne trouve pas assez.

---

## 4. Le multilingue

Le texte vit dans des tables dédiées (`hero_translations`,
`category_translations`), une ligne par langue.

Une colonne JSON `{"ar": "…", "fr": "…"}` aurait été plus rapide à écrire, mais
elle interdit de trier ou d'indexer par langue, et chaque langue ajoutée devient
une migration. Avec une table séparée, ajouter le tamazight n'a demandé que des
lignes supplémentaires dans le seeder.

Le trait `app/Concerns/HasTranslations.php` fournit `tr('champ')` avec un
**fallback champ par champ** : si la fiche amazighe existe mais que sa
biographie est vide, la biographie française s'affiche plutôt qu'un blanc.

`HeroPersister` n'écrit jamais une ligne de traduction entièrement vide. Le
modèle renvoie `zgh` avec tous ses champs à `null` ; insérer cette ligne
casserait le fallback, qui la trouverait et renverrait `null` sans jamais
essayer le français.

Le tamazight s'écrit en tifinagh (`zgh`), de gauche à droite, police
*Noto Sans Tifinagh*. L'extraction laisse volontairement ces champs vides : la
transcription des noms propres en tifinagh n'a pas de norme stable et ne doit
pas être devinée par un modèle. Elle est saisie à la main après validation.

---

## 5. Schéma de données

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

### Idempotence

`hero:search` peut être relancée sur une fiche existante pour l'enrichir. Chaque
méthode d'enregistrement gère la reprise à sa manière : `updateOrCreate` sur la
locale pour les traductions, suppression puis recréation pour le palmarès,
suppression sélective pour les sources — afin qu'une source déjà validée par un
modérateur conserve son statut.

---

## 6. Garde-fous

Aucune fiche générée n'est publiée telle quelle : `status = 'draft'` est écrit
en dur, pas lu depuis la réponse du modèle. Chaque fiche cite ses sources, avec
`is_verified = false` tant qu'un humain ne les a pas contrôlées.

Les instructions imposent de ne rien inventer et de mettre `null` en cas de
doute. Cette règle tient : sur une recherche dont les sources ne contenaient
aucune information biographique, le modèle a renvoyé un palmarès vide plutôt que
de le combler.

L'agent conversationnel est désactivé par défaut (`ai_chat_enabled = false`) et
s'active fiche par fiche. Pour une personne vivante, `ai_chat_mode` reste
`biographical` : l'agent parle *de* la personne, il ne se fait pas passer *pour*
elle. La règle est portée par le schéma, pas seulement par le prompt.

---

## 7. Feuille de route

- [x] **Sprint 1** — Schéma de données, models, seeders, socle multilingue
- [x] **Sprint 2** — Configuration multilingue, tamazight, switcher de langue
- [x] **Sprint 3** — Moteur d'extraction : `hero:search`, agents, persistance
- [ ] **Sprint 4** — Événement `HeroNotFound` et file des demandes
- [ ] **Sprint 5** — Espace administrateur : demandes, brouillons, publication
- [ ] **Sprint 6** — Front public : recherche, fiche héros, i18n, RTL
- [ ] **Sprint 7** — Déploiement (Laravel Forge) et intégration continue
- [ ] **Sprint 8** — Recherche sémantique et agent conversationnel sourcé

---

## 8. Commandes utiles

```bash
php artisan hero:search "Nawal El Moutawakel" --context="athlete championne olympique"
php artisan migrate:fresh --seed
php artisan db:seed --class=CategorySeeder
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

---

## 9. Structure

```
app/
  Ai/Agents/
    HeroExtractor.php             fiche structurée (schéma strict)
    BiographyWriter.php           biographie longue, trois langues
  Concerns/HasTranslations.php    trait de traduction, fallback par champ
  Console/Commands/SearchHero.php orchestration du pipeline
  Http/Middleware/SetLocale.php   résolution de la langue
  Models/                         Hero, Category, Achievement, …
  Services/
    WebSearchService.php          recherche web et premier filtrage
    ContentCleaner.php            réduction du HTML brut
    HeroPersister.php             écriture en base, en transaction
config/ai.php                     fournisseurs et modèles
config/locales.php                langues supportées
database/migrations/              migrations métier
database/seeders/                 catégories et fiches de démonstration
resources/js/                     application React (Inertia)
```
