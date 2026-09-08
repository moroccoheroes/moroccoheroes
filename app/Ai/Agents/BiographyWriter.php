<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Redige la biographie longue, dans un appel separe.
 *
 * HeroExtractor produit deja beaucoup en une seule reponse : quatre
 * langues, un palmares, des sources. Le champ le plus long en faisait
 * les frais et ressortait systematiquement null, quel que soit le
 * modele. Isoler la biographie donne au modele une tache et une seule.
 */
class BiographyWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        Tu rediges la biographie d'une figure marocaine, pour un site du
        Ministere de la Culture et des Sports.

        - Quatre a huit paragraphes en francais, uniquement a partir des
          sources fournies. N'invente aucun fait, aucune date, aucun lieu.
        - Couvre les origines, la formation, la carriere et l'heritage,
          dans la mesure ou les sources en parlent.
        - Ton factuel et sobre. Pas de superlatifs, pas de formules
          d'admiration : les faits suffisent.
        - Traduis ensuite le meme texte en arabe et en anglais.
        - Si les sources ne disent presque rien de la personne, laisse les
          trois champs a null plutot que de combler les trous.
        TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'fr' => $schema->string()
                ->description('Biographie en francais, plusieurs paragraphes separes par des sauts de ligne.')
                ->nullable()
                ->required(),

            'ar' => $schema->string()
                ->description('Meme texte en arabe.')
                ->nullable()
                ->required(),

            'en' => $schema->string()
                ->description('Meme texte en anglais.')
                ->nullable()
                ->required(),
        ];
    }
}