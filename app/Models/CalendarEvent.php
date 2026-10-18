<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evento libero del calendario (riunione, incontro genitori...), di una squadra o di tutta la società.
 */
#[Fillable(['title', 'type', 'team_id', 'user_id', 'date', 'starts_at', 'ends_at', 'location', 'notes'])]
class CalendarEvent extends Model
{
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Chi l'ha creato.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Orario nel formato "18:30 - 20:00" (o solo l'inizio).
     */
    public function timeRange(): string
    {
        return substr($this->starts_at, 0, 5).($this->ends_at ? ' - '.substr($this->ends_at, 0, 5) : '');
    }
}
