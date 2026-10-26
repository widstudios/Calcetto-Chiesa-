<?php

namespace App\Models;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una partita del calendario. Si chiama "Game" perché "match" è una parola riservata in PHP.
 */
#[Fillable(['tournament_id', 'home_team_id', 'away_team_id', 'matchday', 'stage', 'bracket_slot', 'kickoff_at', 'venue', 'home_score', 'away_score', 'home_penalties', 'away_penalties', 'mvp_player_id', 'status', 'csi_code'])]
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
            'stage' => GameStage::class,
            'bracket_slot' => 'integer',
            'home_penalties' => 'integer',
            'away_penalties' => 'integer',
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

    /**
     * Migliore in campo.
     *
     * @return BelongsTo<Player, $this>
     */
    public function mvp(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'mvp_player_id');
    }

    /**
     * Tabellino: gol e voti dei calciatori.
     *
     * @return HasMany<Performance, $this>
     */
    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    public function isKnockout(): bool
    {
        return $this->stage !== null;
    }

    /** Ci sono i rigori da mostrare? */
    public function hasPenalties(): bool
    {
        return $this->isPlayed() && $this->home_penalties !== null && $this->away_penalties !== null;
    }

    /**
     * Chi ha vinto (anche ai rigori), oppure null se la partita non è finita o è un pareggio.
     */
    public function winnerId(): ?int
    {
        if (! $this->isPlayed()) {
            return null;
        }

        $result = $this->home_score <=> $this->away_score;
        if ($result === 0 && $this->hasPenalties()) {
            $result = $this->home_penalties <=> $this->away_penalties;
        }

        return match ($result) {
            1 => $this->home_team_id,
            -1 => $this->away_team_id,
            default => null,
        };
    }

    /**
     * Lettera del girone se le due squadre sono nello stesso girone, altrimenti null.
     */
    public function groupName(): ?string
    {
        if ($this->isKnockout()) {
            return null;
        }

        $group = $this->homeTeam?->group_name;

        return $group && $group === $this->awayTeam?->group_name ? $group : null;
    }

    public function isPlayed(): bool
    {
        return $this->status === GameStatus::Giocata;
    }
}
