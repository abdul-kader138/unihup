<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class DegreeProgram extends Model
{
    use HasFactory;
    use Searchable;

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
        'tuition_min',
        'tuition_max',
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
            'tuition_min' => 'decimal:2',
            'tuition_max' => 'decimal:2',
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

    /** Matches the admin DataFreshnessWidget's "re-verify" threshold. */
    public const STALE_AFTER_DAYS = 90;

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /** True when this record has never been verified, or not in 90+ days. */
    public function isStale(): bool
    {
        return $this->last_verified_at === null
            || $this->last_verified_at->lt(now()->subDays(self::STALE_AFTER_DAYS));
    }

    /** Short human note about when the admission data was last checked. */
    public function verificationLabel(): string
    {
        return $this->last_verified_at === null
            ? 'Not yet verified'
            : 'Checked '.$this->last_verified_at->diffForHumans();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Flattened program + university + subject text for full-text search.
     * The "collection" driver backs a smarter multi-column search than the
     * old per-column LIKEs; point Scout at Meilisearch/Typesense in
     * production for typo tolerance and faceting (see config/scout.php).
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['university', 'subject']);

        return [
            'name' => $this->name,
            'language' => $this->language,
            'degree_level' => $this->degree_level,
            'admission_type' => $this->admission_type,
            'university_name' => $this->university?->display_name,
            'university_city' => $this->university?->city,
            'subject_name' => $this->subject?->display_name,
        ];
    }
}
