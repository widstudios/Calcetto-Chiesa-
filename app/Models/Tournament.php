<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'season', 'starts_on', 'ends_on', 'lineup_max_players', 'description'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'lineup_max_players' => 'integer',
        ];
    }

    /**
     * Quanti giocatori può avere al massimo una distinta: quello del torneo, altrimenti quello generale.
     */
    public function lineupMaxPlayers(): int
    {
        return $this->lineup_max_players ?? Setting::lineupMaxPlayers();
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Calendario delle partite del torneo.
     *
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('kickoff_at');
    }
}
