<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * Documento allegato da un allenatore o dall'admin, visibile a tutti o solo alle persone scelte.
 */
#[Fillable(['title', 'description', 'path', 'original_name', 'mime_type', 'size', 'user_id', 'for_everyone'])]
class Document extends Model
{
    /** Disco privato: i file non hanno un indirizzo pubblico. */
    public const DISK = 'local';

    public const FOLDER = 'documenti';

    protected function casts(): array
    {
        return [
            'for_everyone' => 'boolean',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Document $document) => Storage::disk(self::DISK)->delete($document->path));
    }

    /**
     * Chi ha caricato il documento.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Il file della cartella del CSI da cui viene, se il documento è arrivato con la sincronizzazione.
     *
     * @return HasOne<CsiDocument, $this>
     */
    public function csiDocument(): HasOne
    {
        return $this->hasOne(CsiDocument::class);
    }

    /**
     * Persone scelte che possono vedere il documento (quando non è per tutti).
     *
     * @return BelongsToMany<User, $this>
     */
    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Documenti che l'utente può vedere: l'admin tutti, gli altri quelli per tutti,
     * quelli che hanno caricato e quelli in cui sono stati scelti.
     *
     * @param  Builder<Document>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(fn ($q) => $q->where('for_everyone', true)
            ->orWhere('user_id', $user->id)
            ->orWhereRelation('viewers', 'users.id', $user->id));
    }

    public function isVisibleTo(User $user): bool
    {
        return $user->isAdmin()
            || $this->for_everyone
            || $this->user_id === $user->id
            || $this->viewers()->whereKey($user->id)->exists();
    }

    /**
     * Dimensione leggibile, es. "1,2 MB".
     */
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return str_replace('.', ',', (string) round($size, $i > 1 ? 1 : 0)).' '.$units[$i];
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION)) ?: 'FILE';
    }
}
