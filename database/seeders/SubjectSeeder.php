<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    public const SUBJECTS = [
        'Computer Science',
        'Architecture',
        'Medicine and Surgery',
        'Economics',
        'Law',
        'Mechanical Engineering',
        'Electrical Engineering',
        'Civil Engineering',
        'Physics',
        'Mathematics',
        'Biology',
        'Business Administration',
        'International Relations',
        'Design',
        'Psychology',
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $name) {
            Subject::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
