<?php

namespace App\Models;

use App\Support\DocumentChecklist;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * One document in a student's private vault (passport, Dichiarazione di
 * Valore, admission letter, visa, ...). The file — when uploaded — lives on
 * the private 'local' disk and is only ever served through
 * App\Http\Controllers\StudentDocumentController, gated to the owner.
 */
class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'label', 'status', 'file_path',
        'original_filename', 'mime_type', 'size_bytes', 'notes', 'expires_at',
    ];

    public const DISK = 'local';

    /** Progress state per key (label). */
    public const STATUSES = [
        'needed' => 'Needed',
        'in_progress' => 'In progress',
        'ready' => 'Ready',
        'submitted' => 'Submitted',
        'not_applicable' => 'Not applicable',
    ];

    /** Filament badge colour per status. */
    public const STATUS_COLORS = [
        'needed' => 'danger',
        'in_progress' => 'warning',
        'ready' => 'success',
        'submitted' => 'success',
        'not_applicable' => 'gray',
    ];

    /** Statuses that count as "done" for the vault progress meter. */
    public const DONE_STATUSES = ['ready', 'submitted', 'not_applicable'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Stamp the owner from the session when a caller (e.g. the Filament
        // vault form) didn't set it explicitly.
        static::creating(function (StudentDocument $document) {
            if (! $document->user_id && auth()->hasUser()) {
                $document->user_id = auth()->id();
            }
        });

        // Keep the disk in step with the row: drop the old file when it's
        // replaced or the row is deleted, so nothing is orphaned on disk.
        static::updating(function (StudentDocument $document) {
            if ($document->isDirty('file_path')) {
                $original = $document->getOriginal('file_path');

                if ($original && $original !== $document->file_path) {
                    Storage::disk(self::DISK)->delete($original);
                }
            }
        });

        static::deleting(function (StudentDocument $document) {
            if ($document->file_path) {
                Storage::disk(self::DISK)->delete($document->file_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return DocumentChecklist::TYPES[$this->type]['label'] ?? ($this->label ?: $this->type);
    }

    public function getHasFileAttribute(): bool
    {
        return filled($this->file_path);
    }

    public function getSizeForHumansAttribute(): ?string
    {
        return $this->size_bytes ? Number::fileSize($this->size_bytes) : null;
    }

    public function isDone(): bool
    {
        return in_array($this->status, self::DONE_STATUSES, true);
    }

    /**
     * Fill mime_type / size_bytes from the file currently on disk (or clear
     * them when there is no file). Call this after a save that may have moved
     * an upload into place — see App\Filament\Pages\MyDocuments.
     */
    public function syncFileMeta(): void
    {
        $disk = Storage::disk(self::DISK);
        $onDisk = $this->file_path && $disk->exists($this->file_path);

        $this->mime_type = $onDisk ? ($disk->mimeType($this->file_path) ?: null) : null;
        $this->size_bytes = $onDisk ? ($disk->size($this->file_path) ?: null) : null;

        if ($this->isDirty(['mime_type', 'size_bytes'])) {
            $this->saveQuietly();
        }
    }
}
