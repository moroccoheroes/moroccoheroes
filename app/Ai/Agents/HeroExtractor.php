<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Extrait une fiche biographique a partir de pages web nettoyees.
 *
 * Le schema garantit la forme de la reponse : plus besoin de decrire
 * le JSON dans le prompt ni d'esperer que le modele le respecte.
 * Les instructions ne portent donc que sur le fond.
 *
 * Note : en schema strict, required et nullable ne s'opposent pas.
 * required signifie que la cle doit etre presente, nullable que sa
 * valeur peut etre null. Le modele ne peut donc pas omettre un champ :
 * il doit dire explicitement qu'il ne sait pas.
 */
class HeroExtractor implements Agent, HasStructuredOutput
{
    use Promptable;

    private const CATEGORIES = [
        'football', 'athletisme', 'boxe', 'basketball', 'arts-martiaux',
        'litterature', 'musique', 'cinema', 'arts-visuels',
        'resistance', 'dynasties', 'exploration',
        'sciences', 'spiritualite', 'education',
    ];

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        Tu extrais une fiche biographique a partir des sources fournies, pour un
        site du Ministere de la Culture et des Sports du Maroc.

        REGLES
        - N'invente rien. Toute information absente des sources vaut null.
        - Ne deduis jamais une date a partir d'un age approximatif.
        - Si la personne est decedee, renseigne death_year et mets is_alive a false.
        - Traduis toi-meme en arabe et en anglais a partir du francais.
        - Le champ zgh (tamazight) reste entierement null : la transcription des
          noms propres en tifinagh n'a pas de norme stable et sera saisie a la main.
        - N'inscris dans sources que les URL dont tu t'es reellement servi.
        - Le resume fait deux ou trois phrases, factuelles, sans emphase.
        TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('Identifiant en minuscules, sans accent : prenom-nom')
                ->required(),

            'gender' => $schema->string()
                ->enum(['male', 'female'])
                ->nullable()
                ->required(),

            'birth_date' => $schema->string()
                ->description('Format YYYY-MM-DD. null si la date exacte est inconnue.')
                ->nullable()
                ->required(),

            'birth_year' => $schema->integer()->nullable()->required(),
            'death_year' => $schema->integer()->nullable()->required(),

            'is_alive' => $schema->boolean()->required(),

            'category' => $schema->string()
                ->enum(self::CATEGORIES)
                ->nullable()
                ->required(),

            'translations' => $schema->object([
                'fr'  => $this->translation($schema),
                'ar'  => $this->translation($schema),
                'en'  => $this->translation($schema),
                'zgh' => $this->translation($schema),
            ])->required(),

            'achievements' => $schema->array()->items(
                $schema->object([
                    'year' => $schema->integer()->nullable()->required(),

                    'type' => $schema->string()
                        ->enum(['title', 'medal', 'record', 'award', 'oeuvre', 'other'])
                        ->required(),

                    'title' => $schema->object([
                        'fr' => $schema->string()->required(),
                        'ar' => $schema->string()->required(),
                        'en' => $schema->string()->required(),
                    ])->required(),
                ]),
            )->required(),

            'sources' => $schema->array()
                ->items($schema->string())
                ->required(),
        ];
    }

    private function translation(JsonSchema $schema)
    {
        return $schema->object([
            'name'        => $schema->string()->nullable()->required(),
            'nickname'    => $schema->string()->nullable()->required(),
            'birth_place' => $schema->string()->nullable()->required(),
            'summary'     => $schema->string()->nullable()->required(),
        ])->required();
    }
}