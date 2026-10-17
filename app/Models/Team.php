<?php

namespace App\Models;

use App\Enums\SquadRole;
use Carbon\CarbonInterface;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['tournament_id', 'name', 'coach_name'])]
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
     * Account degli allenatori che gestiscono la squadra (anche più d'uno).
     *
     * @return BelongsToMany<User, $this>
     */
    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coach_team')->orderBy('name');
    }

    public function isCoachedBy(User $user): bool
    {
        return $this->coaches()->whereKey($user->id)->exists();
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
     * Allenamenti settimanali, in ordine di giorno e ora.
     *
     * @return HasMany<Training, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class)->orderBy('weekday')->orderBy('starts_at');
    }

    /**
     * Appello degli allenamenti: presenze e assenze per data.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Esercizi fatti negli allenamenti, segnati con l'appello.
     *
     * @return HasMany<TrainingDrill, $this>
     */
    public function trainingDrills(): HasMany
    {
        return $this->hasMany(TrainingDrill::class);
    }

    /**
     * Distinte della squadra, dalla più recente.
     *
     * @return HasMany<Lineup, $this>
     */
    public function lineups(): HasMany
    {
        return $this->hasMany(Lineup::class)->latest('kickoff_at')->latest('id');
    }

    /**
     * Allenamenti in calendario per una data (stesso giorno della settimana).
     *
     * @return Collection<int, Training>
     */
    public function trainingsOn(CarbonInterface $date): Collection
    {
        return $this->trainings->where('weekday', $date->dayOfWeekIso)->values();
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
        return $this->coach_name ?? ($this->coaches->pluck('name')->join(', ') ?: null);
    }
}
