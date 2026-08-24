<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Hero;
use Illuminate\Database\Seeder;

/**
 * ⚠️ DONNEES DE DEMONSTRATION.
 *
 * Chaque fiche doit etre re-verifiee contre une source primaire (federation,
 * archives nationales, CIO, biographie de reference) AVANT d'etre publiee.
 * Le champ `status` reste volontairement a 'published' ici pour peupler la
 * maquette, mais en production une fiche sans source verifiee ne sort pas.
 */
class HeroSeeder extends Seeder
{
    public function run(): void
    {
        $heroes = [
            [
                'slug' => 'hicham-el-guerrouj',
                'category' => 'athletisme',
                'gender' => 'male',
                'birth_date' => '1974-09-14',
                'is_alive' => true,
                'is_featured' => true,
                'tr' => [
                    'fr' => [
                        'name' => 'Hicham El Guerrouj',
                        'nickname' => 'Le Roi du mile',
                        'birth_place' => 'Berkane',
                        'summary' => 'Double champion olympique à Athènes en 2004 sur 1500 m et 5000 m, détenteur des records du monde du 1500 m et du mile.',
                    ],
                    'ar' => [
                        'name' => 'هشام الكروج',
                        'birth_place' => 'بركان',
                        'summary' => 'بطل أولمبي مزدوج في أثينا 2004 في سباقي 1500 و5000 متر، وصاحب الرقم القياسي العالمي في 1500 متر والميل.',
                    ],
                    'en' => [
                        'name' => 'Hicham El Guerrouj',
                        'birth_place' => 'Berkane',
                        'summary' => 'Double Olympic champion at Athens 2004 over 1500 m and 5000 m, world record holder in the 1500 m and the mile.',
                    ],
                ],
                'achievements' => [
                    ['year' => 1998, 'type' => 'record', 'title' => ['fr' => 'Record du monde du 1500 m', 'ar' => 'الرقم القياسي العالمي 1500 متر', 'en' => '1500 m world record']],
                    ['year' => 1999, 'type' => 'record', 'title' => ['fr' => 'Record du monde du mile',   'ar' => 'الرقم القياسي العالمي للميل',  'en' => 'Mile world record']],
                    ['year' => 2004, 'type' => 'title',  'title' => ['fr' => 'Double titre olympique, Athènes', 'ar' => 'لقبان أولمبيان، أثينا', 'en' => 'Double Olympic title, Athens']],
                ],
            ],
            [
                'slug' => 'nawal-el-moutawakel',
                'category' => 'athletisme',
                'gender' => 'female',
                'birth_date' => '1962-04-15',
                'is_alive' => true,
                'is_featured' => true,
                'tr' => [
                    'fr' => [
                        'name' => 'Nawal El Moutawakel',
                        'birth_place' => 'Casablanca',
                        'summary' => "Championne olympique du 400 m haies à Los Angeles en 1984 : première femme d'un pays arabe et musulman à décrocher l'or olympique.",
                    ],
                    'ar' => [
                        'name' => 'نوال المتوكل',
                        'birth_place' => 'الدار البيضاء',
                        'summary' => 'بطلة أولمبية في سباق 400 متر حواجز بلوس أنجلوس 1984، وأول امرأة من بلد عربي ومسلم تفوز بالذهب الأولمبي.',
                    ],
                    'en' => [
                        'name' => 'Nawal El Moutawakel',
                        'birth_place' => 'Casablanca',
                        'summary' => 'Olympic 400 m hurdles champion at Los Angeles 1984, the first woman from an Arab and Muslim country to win Olympic gold.',
                    ],
                ],
                'achievements' => [
                    ['year' => 1984, 'type' => 'title', 'title' => ['fr' => 'Or olympique, 400 m haies, Los Angeles', 'ar' => 'ذهبية أولمبية، 400 متر حواجز، لوس أنجلوس', 'en' => 'Olympic gold, 400 m hurdles, Los Angeles']],
                ],
            ],
            [
                'slug' => 'ahmed-faras',
                'category' => 'football',
                'gender' => 'male',
                'birth_date' => '1946-12-07',
                'is_alive' => true, // ⚠️ a verifier avant publication
                'tr' => [
                    'fr' => [
                        'name' => 'Ahmed Faras',
                        'birth_place' => 'Mohammédia',
                        'summary' => "Capitaine des Lions de l'Atlas sacrés à la CAN 1976, élu Ballon d'Or africain en 1975.",
                    ],
                    'ar' => [
                        'name' => 'أحمد فرس',
                        'birth_place' => 'المحمدية',
                        'summary' => 'قائد أسود الأطلس المتوجين بكأس إفريقيا 1976، وأفضل لاعب إفريقي سنة 1975.',
                    ],
                    'en' => [
                        'name' => 'Ahmed Faras',
                        'birth_place' => 'Mohammedia',
                        'summary' => 'Captain of the Atlas Lions when they won the 1976 Africa Cup of Nations, African Footballer of the Year in 1975.',
                    ],
                ],
                'achievements' => [
                    ['year' => 1975, 'type' => 'award', 'title' => ['fr' => "Ballon d'Or africain", 'ar' => 'الكرة الذهبية الإفريقية', 'en' => 'African Footballer of the Year']],
                    ['year' => 1976, 'type' => 'title', 'title' => ['fr' => 'Vainqueur de la CAN',   'ar' => 'الفوز بكأس إفريقيا',      'en' => 'Africa Cup of Nations winner']],
                ],
            ],
            [
                'slug' => 'ibn-battuta',
                'category' => 'exploration',
                'gender' => 'male',
                'birth_year' => 1304,
                'death_year' => 1368,
                'date_precision' => 'circa',
                'is_alive' => false,
                'is_featured' => true,
                'tr' => [
                    'fr' => [
                        'name' => 'Ibn Battuta',
                        'birth_place' => 'Tanger',
                        'summary' => "Voyageur né à Tanger au XIVᵉ siècle, auteur de la Rihla, récit de près de trente ans de voyages à travers l'Afrique, l'Asie et l'Europe.",
                    ],
                    'ar' => [
                        'name' => 'ابن بطوطة',
                        'birth_place' => 'طنجة',
                        'summary' => 'رحّالة من طنجة في القرن الرابع عشر، صاحب "الرحلة" التي دوّن فيها نحو ثلاثين سنة من الأسفار عبر إفريقيا وآسيا وأوروبا.',
                    ],
                    'en' => [
                        'name' => 'Ibn Battuta',
                        'birth_place' => 'Tangier',
                        'summary' => 'A 14th-century traveller from Tangier, author of the Rihla, an account of nearly thirty years of journeys across Africa, Asia and Europe.',
                    ],
                ],
                'achievements' => [
                    ['year' => 1355, 'type' => 'publication', 'title' => ['fr' => 'Dictée de la Rihla', 'ar' => 'إملاء كتاب الرحلة', 'en' => 'Dictation of the Rihla']],
                ],
            ],
            [
                'slug' => 'fatima-al-fihri',
                'category' => 'education',
                'gender' => 'female',
                'birth_year' => 800,
                'date_precision' => 'circa',
                'is_alive' => false,
                'is_featured' => true,
                'tr' => [
                    'fr' => [
                        'name' => 'Fatima al-Fihri',
                        'birth_place' => 'Kairouan',
                        'summary' => "Fondatrice en 859 à Fès de la mosquée al-Qarawiyyin, autour de laquelle s'est développé l'un des plus anciens centres d'enseignement du monde.",
                    ],
                    'ar' => [
                        'name' => 'فاطمة الفهرية',
                        'birth_place' => 'القيروان',
                        'summary' => 'مؤسِّسة جامع القرويين بفاس سنة 859، الذي نشأ حوله واحد من أقدم مراكز التعليم في العالم.',
                    ],
                    'en' => [
                        'name' => 'Fatima al-Fihri',
                        'birth_place' => 'Kairouan',
                        'summary' => 'Founder in 859 of the al-Qarawiyyin mosque in Fes, around which one of the oldest centres of learning in the world developed.',
                    ],
                ],
                'achievements' => [
                    ['year' => 859, 'type' => 'work', 'title' => ['fr' => 'Fondation de al-Qarawiyyin', 'ar' => 'تأسيس جامع القرويين', 'en' => 'Founding of al-Qarawiyyin']],
                ],
            ],
        ];

        foreach ($heroes as $data) {
            $category = Category::where('slug', $data['category'])->first();

            $hero = Hero::updateOrCreate(['slug' => $data['slug']], [
                'category_id' => $category?->id,
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'] ?? null,
                'birth_year' => $data['birth_year'] ?? (isset($data['birth_date']) ? (int) substr($data['birth_date'], 0, 4) : null),
                'death_year' => $data['death_year'] ?? null,
                'date_precision' => $data['date_precision'] ?? 'day',
                'is_alive' => $data['is_alive'],
                'is_featured' => $data['is_featured'] ?? false,
                'status' => 'published',
                'published_at' => now(),
                'ai_chat_enabled' => false, // active manuellement par un moderateur
            ]);

            foreach ($data['tr'] as $locale => $fields) {
                $hero->translations()->updateOrCreate(['locale' => $locale], $fields);
            }

            $hero->achievements()->delete();
            foreach ($data['achievements'] as $i => $a) {
                $hero->achievements()->create([...$a, 'sort_order' => $i]);
            }
        }
    }
}
