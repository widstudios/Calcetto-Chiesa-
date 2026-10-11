<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'season', 'starts_on', 'ends_on', 'description'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
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
