<?php

return <<<'TXT'
Tu extrais une fiche biographique a partir des sources ci-dessous.

REGLES STRICTES
- N'invente rien. Si une information n'est pas dans les sources, mets null.
- Les traductions ar : traduis toi-meme a partir du francais.
- Le champ zgh reste null.
- Le champ sources doit lister les URL reellement utilisees.

Reponds UNIQUEMENT avec ce JSON, sans texte autour :

{
  "slug": "prenom-nom",
  "gender": "male|female",
  "birth_date": "YYYY-MM-DD ou null",
  "birth_year": 1962,
  "death_year": null,
  "is_alive": true,
  "category": "athletisme",
  "translations": {
    "fr": {"name":"", "nickname":null, "birth_place":"", "summary":""},
    "ar": {"name":"", "nickname":null, "birth_place":"", "summary":""},
    "en": {"name":"", "nickname":null, "birth_place":"", "summary":""},
    "zgh": {"name":null, "nickname":null, "birth_place":null, "summary":null}
  },
  "achievements": [
    {"year":1984, "type":"title", "title":{"fr":"", "ar":"", "en":""}}
  ],
  "sources": ["url1", "url2"]
}

SOURCES :

TXT;