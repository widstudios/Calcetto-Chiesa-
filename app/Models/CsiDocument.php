<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documento (di solito un PDF del Comunicato Ufficiale) preso dalla cartella Drive del CSI Roma.
 */
class CsiDocument extends Model
{
    protected $fillable = ['drive_id', 'name', 'mime_type', 'size', 'drive_modified_at', 'path', 'downloaded_at', 'imported_at', 'games_found', 'games_saved', 'import_note'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'drive_modified_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'imported_at' => 'datetime',
            'games_found' => 'integer',
            'games_saved' => 'integer',
        ];
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /** Peso leggibile, per esempio "420 KB". */
    public function humanSize(): string
    {
        if (! $this->size) {
            return '';
        }

        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1, ',', '.').' MB' : max(1, round($this->size / 1024)).' KB';
    }
}
