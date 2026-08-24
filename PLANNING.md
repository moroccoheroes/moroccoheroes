# MoroccoHeroes — planning de stage (8 semaines)

Dates si tu démarres le lundi 24 août 2026. Décale tout si tu commences plus tard.

**Objectif final :** un site en ligne, en 3 langues, avec une recherche sémantique et un agent conversationnel sourcé — plus un repo GitHub qu'un recruteur peut lire en 3 minutes.

---

## Semaine 1 — Socle technique
**24 – 30 août · Livrable : `php artisan migrate --seed` tourne, repo GitHub en ligne**

- Installer Herd, PostgreSQL, créer le projet (`laravel new moroccoheroes --react`)
- Copier migrations, models, seeders (déjà prêts)
- Repo GitHub + `.gitignore` + README initial + branche `main` protégée
- Auth du starter kit (login admin, pas d'inscription publique pour l'instant)
- Configurer `config/app.php` : locales `ar`, `fr`, `en`

**Fini quand :** tu ouvres `moroccoheroes.test`, tu te connectes, et `Hero::published()->count()` renvoie 5.

---

## Semaine 2 — Back-office
**31 août – 6 septembre · Livrable : créer un héros complet sans toucher au code**

- `composer require filament/filament`
- Resources : Hero (avec onglets par langue), Category, Achievement, TimelineEvent, Media, Source
- Upload d'images avec recadrage
- Écran de modération des `hero_submissions`
- Rôles : admin / modérateur

**Puis — et ne sous-estime pas ça — saisis 20 à 25 héros réels.** Compte 1,5 jour. Sans données réelles, tout ce qui suit se teste dans le vide : la recherche sémantique sur 5 fiches ne prouve rien.

Répartis-les : sport, culture, histoire, savoir. Hommes et femmes. Époques différentes. Chacun avec au moins 2 sources vérifiées.

---

## Semaine 3 — Front public
**7 – 13 septembre · Livrable : parcours visiteur complet**

- Layout, header, footer, navigation
- Accueil : héros en avant, entrées par catégorie
- Liste avec filtres (catégorie, époque, genre) et pagination
- **Fiche héros** ← la page à soigner. Portrait, chiffres clés, biographie, frise chronologique, palmarès, galerie, sources, figures liées
- Recherche provisoire : un simple `LIKE %query%`. Moche, mais fonctionnel.

C'est la page que verra un recruteur. Investis-y plus qu'ailleurs.

---

## Semaine 4 — Multilingue et mise en ligne ⚑
**14 – 20 septembre · Livrable : le site est accessible publiquement**

- 3 langues : fichiers de traduction + i18next côté React
- Sélecteur de langue, RTL pour l'arabe (`dir="rtl"`, Tailwind logical properties)
- SEO : meta par langue, `hreflang`, sitemap, Open Graph
- Déploiement (Laravel Cloud ou Forge + VPS), domaine, HTTPS
- GitHub Actions : lint + tests à chaque push

**Étape la plus importante du planning.** Un projet en ligne à moitié fini vaut mieux qu'un projet parfait en local. À partir d'ici tu déploies en continu.

---

## Semaine 5 — Service Python : recherche
**21 – 27 septembre · Livrable : recherche sémantique en production**

Nouveau repo : `moroccoheroes-ml` (FastAPI + Docker).

- Ingestion : Laravel envoie les fiches, le service découpe en chunks
- Embeddings, stockés dans `hero_chunks`
- BM25 (lexical) + dense (sémantique) → fusion par **Reciprocal Rank Fusion**
- Normalisation arabe : hamza أ/إ/ا, ta marbouta ة/ه, diacritiques, translittération (« Guerrouj » doit trouver « الكروج »)
- Endpoint `POST /search`

Côté Laravel : client HTTP avec timeout court et **fallback sur la recherche `LIKE`** si le service ne répond pas. Le site ne doit jamais tomber parce que Python est down.

C'est ta semaine la plus dense — et la plus intéressante. ~400 lignes de Python.

---

## Semaine 6 — Agent conversationnel
**28 septembre – 4 octobre · Livrable : discuter avec 3 à 5 fiches**

- Endpoint `POST /chat` : retrieval → construction du prompt → réponse
- **Citations obligatoires** : chaque réponse renvoie les `source_id` utilisés, affichés sous le message
- Streaming (SSE) et interface de chat React
- Persistance dans `chat_sessions` / `chat_messages`
- Garde-fous : `ai_chat_enabled` par fiche, mode `biographical` pour les vivants, bandeau « reconstitution IA », refus explicite quand le contexte ne contient pas la réponse

Les citations sont ce qui distingue ton projet d'un chatbot fait en un week-end. Ne les coupe pas.

---

## Semaine 7 — Solidité
**5 – 11 octobre · Livrable : ça tient debout devant un inconnu**

- `benchmark.md` : 20 requêtes écrites à la main, tu vérifies que le bon héros sort dans le top 3. 30 minutes, pas une semaine.
- Cache des embeddings, rate limiting sur `/chat`, gestion des erreurs
- Tests Pest sur les parcours critiques (fiche, recherche, chat)
- Accessibilité (contrastes, navigation clavier, `alt`), performance (images, lazy loading)
- Test sur mobile réel, en arabe

---

## Semaine 8 — Vitrine
**12 – 18 octobre · Livrable : le projet est présentable**

- README avec captures d'écran, schéma d'architecture, instructions d'installation
- Page « Méthodologie » sur le site : comment les fiches sont vérifiées, comment fonctionne l'agent, quelles sont ses limites
- Vidéo de démo de 2 minutes
- Nettoyage du repo, historique Git lisible
- Post LinkedIn avec le lien

---

## Règles de survie

Pas de jury, pas d'encadrant qui relance : le risque n'est pas technique, il est de **s'arrêter en semaine 5**. Trois garde-fous :

**Fixe une date de démo publique** dès maintenant — le 18 octobre. Dis-le à quelqu'un. Une deadline non partagée n'existe pas.

**Commit tous les jours**, même 10 lignes. Le graphe GitHub est ton contrôle continu.

**Vendredi = point d'étape.** Trois lignes dans un fichier `JOURNAL.md` : fait / bloqué / semaine prochaine. Ça devient ton README final.

## Si tu prends du retard

Coupe dans cet ordre, sans hésiter :

1. L'anglais (garde arabe + français)
2. Les tests automatisés (garde les tests manuels)
3. La frise chronologique
4. Le graphe de figures liées
5. Le `benchmark.md`

**Ne coupe jamais :** le déploiement de la semaine 4, les citations de l'agent, la qualité de la fiche héros.

## Ce qui reste hors périmètre

PySpark, Airflow, fine-tuning d'AraBERT, embeddings de graphe. Ce sont de vrais sujets, mais sur 50 fiches et 8 semaines ils coûtent des semaines pour un résultat inférieur à ce que tu obtiens autrement. Note-les dans le README sous « pistes d'évolution » — ça montre que tu connais le terrain sans avoir dispersé ton temps.
