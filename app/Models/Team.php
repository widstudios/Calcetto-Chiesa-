<?php

namespace App\Models;

use App\Enums\SquadRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tournament_id', 'name', 'coach_name', 'coach_id'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Account dell'allenatore, se ne ha uno.
     *
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function starters(): HasMany
    {
        return $this->players()->where('squad_role', SquadRole::Titolare);
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function bench(): HasMany
    {
        return $this->players()->where('squad_role', SquadRole::Panchina);
    }

    /**
     * Tutte le partite della squadra, in casa e in trasferta.
     *
     * @return Builder<Game>
     */
    public function games(): Builder
    {
        return Game::query()
            ->where(fn (Builder $q) => $q->where('home_team_id', $this->id)->orWhere('away_team_id', $this->id))
            ->orderBy('kickoff_at');
    }

    /**
     * Nome dell'allenatore: quello inserito a mano o, in mancanza, quello del suo account.
     */
    public function coachDisplayName(): ?string
    {
        return $this->coach_name ?? $this->coach?->name;
    }
}
