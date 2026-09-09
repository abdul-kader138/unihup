<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One question/answer in the student Help Center. Staff-managed
 * (FaqResource); students search and read them on
 * App\Filament\Pages\HelpCenter. `answer` is Markdown.
 */
class FaqEntry extends Model
{
    protected $fillable = [
        'question', 'slug', 'answer', 'category', 'tags', 'sort', 'is_published',
    ];

    public const CATEGORIES = [
        'General',
        'Choosing a program',
        'Applications',
        'Admission tests',
        'Documents & recognition',
        'Visa & arrival',
        'Money & scholarships',
        'Living in Italy',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FaqEntry $entry) {
            if (blank($entry->slug) || $entry->isDirty('question')) {
                $entry->slug = Str::slug(Str::limit($entry->question, 60, ''));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('question', 'like', "%{$term}%")
                ->orWhere('answer', 'like', "%{$term}%");
        });
    }

    public function answerHtml(): string
    {
        return Str::markdown($this->answer ?? '');
    }
}
