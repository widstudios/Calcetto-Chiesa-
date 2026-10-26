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
use Illuminate\Support\Facades\Storage;

#[Fillable(['tournament_id', 'name', 'group_name', 'coach_name', 'logo', 'shirt_color', 'shirt_color_secondary'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Colori che si possono scegliere per la maglia.
     *
     * @var array<string, string>
     */
    public const SHIRT_COLORS = [
        '#ffffff' => 'Bianco',
        '#111827' => 'Nero',
        '#9ca3af' => 'Grigio',
        '#dc2626' => 'Rosso',
        '#7f1d1d' => 'Granata',
        '#f97316' => 'Arancione',
        '#facc15' => 'Giallo',
        '#16a34a' => 'Verde',
        '#14532d' => 'Verde scuro',
        '#38bdf8' => 'Azzurro',
        '#1d4ed8' => 'Blu',
        '#1e3a8a' => 'Blu notte',
        '#7c3aed' => 'Viola',
        '#ec4899' => 'Rosa',
    ];

    protected static function booted(): void
    {
        // Eliminata la squadra, il suo stemma non serve più.
        static::deleted(fn (Team $team) => $team->logo && Storage::disk('uploads')->delete($team->logo));
    }

    /** «Girone A», oppure null se la squadra non è in un girone. */
    public function groupLabel(): ?string
    {
        return $this->group_name ? 'Girone '.$this->group_name : null;
    }

    /** Indirizzo dello stemma caricato, oppure null. */
    public function logoUrl(): ?string
    {
        return $this->logo ? asset('uploads/'.$this->logo) : null;
    }

    /**
     * Colori della maglia scelti (uno o due).
     *
     * @return list<string>
     */
    public function shirtColors(): array
    {
        return array_values(array_unique(array_filter([$this->shirt_color, $this->shirt_color_secondary])));
    }

    /** Colori della maglia a parole, per esempio «Rosso e Bianco». */
    public function shirtColorsLabel(): ?string
    {
        $names = array_map(fn ($hex) => self::SHIRT_COLORS[$hex] ?? $hex, $this->shirtColors());

        return $names ? implode(' e ', $names) : null;
    }

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
