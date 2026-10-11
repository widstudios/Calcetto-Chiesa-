<?php

namespace App\Models;

use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una partita del calendario. Si chiama "Game" perché "match" è una parola riservata in PHP.
 */
#[Fillable(['tournament_id', 'home_team_id', 'away_team_id', 'matchday', 'kickoff_at', 'venue', 'home_score', 'away_score', 'status'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'matchday' => 'integer',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'status' => GameStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function isPlayed(): bool
    {
        return $this->status === GameStatus::Giocata;
    }
}
