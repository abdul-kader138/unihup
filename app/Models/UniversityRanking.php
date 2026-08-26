<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_id',
        'edition',
        'category',
        'position',
        'score_services',
        'score_scholarships',
        'score_facilities',
        'score_communication_digital',
        'score_internationalization',
        'score_employability',
        'overall_score',
        'source_url',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'float',
            'last_verified_at' => 'datetime',
        ];
    }

    /** CENSIS's own groupings — state and private universities are ranked in separate tables, each banded by enrollment size. */
    public const CATEGORIES = [
        'mega_statali' => 'Mega state universities (40,000+ students)',
        'grandi_statali' => 'Large state universities (20,000-40,000)',
        'medi_statali' => 'Medium state universities (10,000-20,000)',
        'piccoli_statali' => 'Small state universities (up to 10,000)',
        'politecnici' => 'Polytechnics',
        'grandi_non_statali' => 'Large private universities (10,000+)',
        'medi_non_statali' => 'Medium private universities (5,000-10,000)',
        'piccoli_non_statali' => 'Small private universities (up to 5,000)',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
