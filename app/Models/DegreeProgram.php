<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DegreeProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_id',
        'subject_id',
        'degree_level',
        'name',
        'language',
        'duration_years',
        'admission_type',
        'admission_notes',
        'tuition_note',
        'application_window_note',
        'official_admission_url',
        'source_url',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
            'duration_years' => 'integer',
        ];
    }

    public const DEGREE_LEVELS = [
        'bachelor' => "Bachelor's / Honours",
        'master' => "Master's",
    ];

    public const ADMISSION_TYPES = [
        'open' => 'Open access',
        'restricted' => 'Restricted (numero programmato)',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
