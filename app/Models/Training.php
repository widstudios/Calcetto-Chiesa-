<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allenamento settimanale di una squadra: giorno, campo e orario.
 */
#[Fillable(['team_id', 'weekday', 'field', 'starts_at', 'ends_at'])]
class Training extends Model
{
    public const WEEKDAYS = [
        1 => 'Lunedì',
        2 => 'Martedì',
        3 => 'Mercoledì',
        4 => 'Giovedì',
        5 => 'Venerdì',
        6 => 'Sabato',
        7 => 'Domenica',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday];
    }

    /**
     * Orario nel formato "18:30 - 20:00".
     */
    public function timeRange(): string
    {
        return substr($this->starts_at, 0, 5).' - '.substr($this->ends_at, 0, 5);
    }
}
