<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Traductions amazighes (zgh) en Tifinagh.
 *
 * ⚠️ Les termes marques "A VALIDER" sont des propositions non confirmees.
 * A faire relire par un locuteur ou a verifier dans les lexiques de l'IRCAM
 * (ircam.ma) avant toute mise en ligne publique.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            ['sport', 'trophy', '#1D9E75',
                ['ar' => 'رياضة', 'fr' => 'Sport', 'en' => 'Sport', 'zgh' => 'ⴰⴷⴷⴰⵍ'], [
                ['football',      ['ar' => 'كرة القدم',   'fr' => 'Football',      'en' => 'Football']],
                ['athletisme',    ['ar' => 'ألعاب القوى', 'fr' => 'Athlétisme',    'en' => 'Athletics']],
                ['boxe',          ['ar' => 'الملاكمة',     'fr' => 'Boxe',          'en' => 'Boxing']],
                ['basketball',    ['ar' => 'كرة السلة',    'fr' => 'Basketball',    'en' => 'Basketball']],
                ['arts-martiaux', ['ar' => 'فنون قتالية',  'fr' => 'Arts martiaux', 'en' => 'Martial arts']],
            ]],
            ['culture', 'palette', '#7F77DD',
                ['ar' => 'ثقافة', 'fr' => 'Culture', 'en' => 'Culture', 'zgh' => 'ⵜⴰⴷⵍⵙⴰ'], [
                ['litterature',  ['ar' => 'أدب',          'fr' => 'Littérature',  'en' => 'Literature', 'zgh' => 'ⵜⴰⵙⴽⵍⴰ']],
                ['musique',      ['ar' => 'موسيقى',       'fr' => 'Musique',      'en' => 'Music',      'zgh' => 'ⴰⵥⴰⵡⴰⵏ']],
                ['cinema',       ['ar' => 'سينما',        'fr' => 'Cinéma',       'en' => 'Cinema']],
                ['arts-visuels', ['ar' => 'فنون تشكيلية', 'fr' => 'Arts visuels', 'en' => 'Visual arts']],
            ]],
            ['histoire', 'landmark', '#D85A30',
                ['ar' => 'تاريخ', 'fr' => 'Histoire', 'en' => 'History', 'zgh' => 'ⴰⵎⵣⵔⵓⵢ'], [
                ['resistance',  ['ar' => 'مقاومة',   'fr' => 'Résistance',  'en' => 'Resistance']],
                ['dynasties',   ['ar' => 'دول وأسر', 'fr' => 'Dynasties',   'en' => 'Dynasties']],
                ['exploration', ['ar' => 'استكشاف',  'fr' => 'Exploration', 'en' => 'Exploration']],
            ]],
            ['savoir', 'book-open', '#378ADD',
                ['ar' => 'علم ومعرفة', 'fr' => 'Savoir', 'en' => 'Knowledge', 'zgh' => 'ⵜⵓⵙⵙⵏⴰ'], [
                ['sciences',     ['ar' => 'علوم',      'fr' => 'Sciences',     'en' => 'Sciences']],
                ['spiritualite', ['ar' => 'تصوف وفقه', 'fr' => 'Spiritualité', 'en' => 'Spirituality']],
                ['education',    ['ar' => 'تعليم',     'fr' => 'Éducation',    'en' => 'Education', 'zgh' => 'ⴰⵙⵙⵍⵎⴷ']],
            ]],
        ];

        foreach ($tree as $i => [$slug, $icon, $color, $names, $children]) {
            $parent = $this->make($slug, $names, $i, $icon, $color);

            foreach ($children as $j => [$childSlug, $childNames]) {
                $this->make($childSlug, $childNames, $j, null, null, $parent->id);
            }
        }
    }

    private function make(string $slug, array $names, int $order, ?string $icon = null, ?string $color = null, ?int $parentId = null): Category
    {
        $category = Category::updateOrCreate(
            ['slug' => $slug],
            ['parent_id' => $parentId, 'icon' => $icon, 'color' => $color, 'sort_order' => $order, 'is_active' => true],
        );

        foreach ($names as $locale => $name) {
            $category->translations()->updateOrCreate(['locale' => $locale], ['name' => $name]);
        }

        return $category;
    }
}
