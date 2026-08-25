<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UniversitySeeder extends Seeder
{
    /**
     * A representative, real set of Italian universities — not exhaustive.
     * See App\Services\Universities\SeedDataImporter for how this fits into
     * the pluggable import pipeline.
     */
    public const UNIVERSITIES = [
        ['name' => 'University of Bologna', 'city' => 'Bologna', 'region' => 'Emilia-Romagna', 'website_url' => 'https://www.unibo.it', 'description' => "Founded in 1088, widely considered the oldest university in the Western world."],
        ['name' => 'Sapienza University of Rome', 'city' => 'Rome', 'region' => 'Lazio', 'website_url' => 'https://www.uniroma1.it', 'description' => 'One of the largest universities in Europe by enrollment.'],
        ['name' => 'Politecnico di Milano', 'city' => 'Milan', 'region' => 'Lombardy', 'website_url' => 'https://www.polimi.it', 'description' => "Italy's largest technical university — engineering, architecture, and design."],
        ['name' => 'Politecnico di Torino', 'city' => 'Turin', 'region' => 'Piedmont', 'website_url' => 'https://www.polito.it', 'description' => 'A leading technical university for engineering and architecture.'],
        ['name' => 'University of Milan', 'city' => 'Milan', 'region' => 'Lombardy', 'website_url' => 'https://www.unimi.it', 'description' => "Also known as \"La Statale\" — a large public research university."],
        ['name' => 'University of Padua', 'city' => 'Padua', 'region' => 'Veneto', 'website_url' => 'https://www.unipd.it', 'description' => 'Founded in 1222, one of the oldest universities in the world.'],
        ['name' => 'University of Florence', 'city' => 'Florence', 'region' => 'Tuscany', 'website_url' => 'https://www.unifi.it', 'description' => 'A major public research university in Tuscany.'],
        ['name' => 'University of Naples Federico II', 'city' => 'Naples', 'region' => 'Campania', 'website_url' => 'https://www.unina.it', 'description' => 'Founded in 1224, the oldest state-funded university in the world.'],
        ['name' => 'University of Turin', 'city' => 'Turin', 'region' => 'Piedmont', 'website_url' => 'https://www.unito.it', 'description' => 'One of the largest universities in Italy, founded in 1404.'],
        ['name' => 'University of Pisa', 'city' => 'Pisa', 'region' => 'Tuscany', 'website_url' => 'https://www.unipi.it', 'description' => 'Founded in 1343, known for strong science and engineering programs.'],
        ['name' => 'Bocconi University', 'city' => 'Milan', 'region' => 'Lombardy', 'website_url' => 'https://www.unibocconi.it', 'description' => 'A private university specializing in economics, management, and law.'],
        ['name' => 'University of Trento', 'city' => 'Trento', 'region' => 'Trentino-Alto Adige', 'website_url' => 'https://www.unitn.it', 'description' => 'A research-intensive public university, highly ranked relative to its size.'],
        ["name" => "Ca' Foscari University of Venice", 'city' => 'Venice', 'region' => 'Veneto', 'website_url' => 'https://www.unive.it', 'description' => 'Specializes in economics, languages, and international studies.'],
        ['name' => 'University of Genoa', 'city' => 'Genoa', 'region' => 'Liguria', 'website_url' => 'https://www.unige.it', 'description' => 'A public research university founded in 1481.'],
        ['name' => 'University of Bari Aldo Moro', 'city' => 'Bari', 'region' => 'Apulia', 'website_url' => 'https://www.uniba.it', 'description' => 'The largest university in southern Italy outside Naples.'],
    ];

    public function run(): void
    {
        foreach (self::UNIVERSITIES as $university) {
            University::updateOrCreate(
                ['slug' => Str::slug($university['name'])],
                $university,
            );
        }
    }
}
