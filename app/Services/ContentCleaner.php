<?php

namespace App\Services;

/**
 * Reduit une page web brute a du texte exploitable par un modele.
 *
 * En pratique cette etape fait passer un article encyclopedique
 * d'environ 75 000 caracteres a moins de 6 000, en supprimant ce
 * qui n'a aucune valeur pour l'extraction : images encodees en
 * base64, liens, tableaux de navigation, listes de references.
 */
class ContentCleaner
{
    private const MAX_LENGTH = 6000;

    /** Sections qui marquent la fin du contenu utile. */
    private const CUT_AT = ['References', 'External links', 'See also', 'Notes', 'Bibliography'];

    public function clean(string $raw, ?int $maxLength = null): string
    {
        $text = $raw;

        $text = $this->removeImages($text);
        $text = $this->unwrapLinks($text);
        $text = $this->cutAtSections($text);
        $text = $this->removeNavigationLines($text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return mb_substr(trim($text), 0, $maxLength ?? self::MAX_LENGTH);
    }

    /** Les images encodees en base64 peuvent representer 90 % d'une page. */
    private function removeImages(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\(data:[^)]*\)/', '', $text);

        return preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text);
    }

    /** Garde le libelle du lien, jette l'URL. */
    private function unwrapLinks(string $text): string
    {
        return preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
    }

    private function cutAtSections(string $text): string
    {
        $pattern = '/\n\s*#{1,3}\s*('.implode('|', self::CUT_AT).')/i';
        $text = preg_split($pattern, $text)[0];

        return preg_split('/\n\s*Categories:/i', $text)[0];
    }

    /**
     * Une ligne courte sans point est presque toujours de la navigation
     * ou une entree de tableau, pas de la prose.
     */
    private function removeNavigationLines(string $text): string
    {
        return collect(explode("\n", $text))
            ->reject(fn (string $line) => strlen(trim($line)) < 40 && ! str_contains($line, '.'))
            ->implode("\n");
    }
}