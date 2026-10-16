<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Distinta di una squadra: chi va in campo, per una partita del calendario o per una gara qualsiasi.
 */
#[Fillable(['team_id', 'game_id', 'kickoff_at', 'opponent', 'venue'])]
class Lineup extends Model
{
    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
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
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Convocati con maglia e fascia.
     *
     * @return HasMany<LineupPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(LineupPlayer::class);
    }

    /**
     * "Oratorio United – San Giuseppe", oppure il solo nome della squadra se manca l'avversario.
     */
    public function title(): string
    {
        if ($this->game) {
            return $this->game->homeTeam->name.' – '.$this->game->awayTeam->name;
        }

        return $this->opponent ? $this->team->name.' – '.$this->opponent : $this->team->name;
    }
}
