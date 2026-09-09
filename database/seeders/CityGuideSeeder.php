<?php

namespace Database\Seeders;

use App\Models\CityGuide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter city guides for the biggest student destinations. Indicative
 * curated overviews — staff refine them in the City Guides resource.
 */
class CityGuideSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            [
                'city' => 'Milan',
                'region' => 'Lombardy',
                'intro' => 'Italy\'s business and design capital and its most expensive city — great job and internship prospects, but budget carefully for rent.',
                'housing' => 'The tightest, priciest student market in Italy. Start looking months ahead; a room in a shared flat commonly runs €600–800/month. Look at university residence halls (e.g. via DSU Lombardia), and neighbourhoods like Città Studi, Lambrate, Bicocca and Bovisa near the campuses.',
                'cost_of_living' => 'Plan for roughly €950–1,400/month all-in including rent. Public transport is about €22/month for under-27 students. Eating out is noticeably dearer than the south.',
                'transport' => 'Four metro lines plus trams and buses (ATM) cover the city well; most students don\'t need a car. Malpensa and Linate airports both have direct rail/coach links.',
                'student_life' => 'Huge student population across Politecnico, Statale, Bicocca, Bocconi and Cattolica. Aperitivo culture, Navigli nightlife, and easy weekend trains to the lakes, the Alps and the rest of Europe.',
                'safety' => 'Generally safe; usual big-city care around Centrale station and on crowded transport for pickpocketing. Register your residence and get your codice fiscale early to sign a lease.',
                'useful_links' => [
                    ['label' => 'DSU Lombardia (right to study)', 'url' => 'https://www.dsu.lombardia.it'],
                    ['label' => 'ATM public transport', 'url' => 'https://www.atm.it/en'],
                ],
            ],
            [
                'city' => 'Bologna',
                'region' => 'Emilia-Romagna',
                'intro' => 'Home to the oldest university in the Western world and the archetypal Italian student city — central, walkable, politically lively.',
                'housing' => 'Demand is high relative to size; a room typically €350–500/month. The historic centre fills fast — also look at Bolognina and the areas along the university district off Via Zamboni. ER.GO runs regional student housing and grants.',
                'cost_of_living' => 'Roughly €700–1,000/month all-in. Famously good, affordable food; a student transport pass is cheap.',
                'transport' => 'Compact and flat — most students walk or cycle. Buses run by TPER; the central station is a major hub with fast trains to Florence (~35 min), Milan and Rome.',
                'student_life' => 'Dense student scene around Via Zamboni and Piazza Verdi, cheap osterie, live music, and a strong Erasmus community.',
                'safety' => 'Safe and student-friendly; ordinary care with bikes (lock them well) and around the station at night.',
                'useful_links' => [
                    ['label' => 'ER.GO (regional right to study)', 'url' => 'https://www.er-go.it'],
                    ['label' => 'TPER public transport', 'url' => 'https://www.tper.it'],
                ],
            ],
            [
                'city' => 'Turin',
                'region' => 'Piedmont',
                'intro' => 'An elegant former royal capital with a strong engineering tradition (Politecnico di Torino) and lower costs than Milan.',
                'housing' => 'More affordable and less frantic than Milan — a room often €300–450/month. San Salvario and Vanchiglia are popular with students; EDISU Piemonte offers residences and scholarships.',
                'cost_of_living' => 'Around €700–1,000/month all-in. Good-value markets (Porta Palazzo) and historic cafés.',
                'transport' => 'One automated metro line plus an extensive tram/bus network (GTT). Grid layout makes it easy to navigate; direct trains to Milan in about an hour.',
                'student_life' => 'Big Politecnico and Università di Torino populations, riverside aperitivo in San Salvario, and the Alps an hour away for skiing.',
                'safety' => 'Generally safe; normal caution around Porta Nuova/Porta Palazzo late at night.',
                'useful_links' => [
                    ['label' => 'EDISU Piemonte', 'url' => 'https://www.edisu.piemonte.it'],
                    ['label' => 'GTT public transport', 'url' => 'https://www.gtt.to.it'],
                ],
            ],
        ];

        foreach ($cities as $city) {
            CityGuide::updateOrCreate(
                ['slug' => Str::slug($city['city'])],
                array_merge($city, ['is_published' => true, 'last_verified_at' => now()]),
            );
        }
    }
}
