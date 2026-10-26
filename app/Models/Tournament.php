<?php

namespace App\Models;

use App\Enums\TournamentFormat;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'season', 'format', 'groups_count', 'qualifiers_per_group', 'starts_on', 'ends_on', 'lineup_max_players', 'description'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    /** Massimo di gironi che si possono creare in un torneo. */
    public const MAX_GROUPS = 8;

    protected $attributes = ['format' => 'campionato'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'lineup_max_players' => 'integer',
            'format' => TournamentFormat::class,
            'groups_count' => 'integer',
            'qualifiers_per_group' => 'integer',
        ];
    }

    public function hasGroups(): bool
    {
        return $this->format === TournamentFormat::Gironi;
    }

    /**
     * Lettere dei gironi del torneo: A, B, C... (nessuna se è un campionato).
     *
     * @return list<string>
     */
    public function groupNames(): array
    {
        if (! $this->hasGroups()) {
            return [];
        }

        return array_slice(range('A', 'Z'), 0, max(1, min(self::MAX_GROUPS, $this->groups_count ?? 2)));
    }

    /**
     * Quanti giocatori può avere al massimo una distinta: quello del torneo, altrimenti quello generale.
     */
    public function lineupMaxPlayers(): int
    {
        return $this->lineup_max_players ?? Setting::lineupMaxPlayers();
    }

    /**
     * Partite della fase finale, turno per turno e in ordine di tabellone.
     *
     * @return HasMany<Game, $this>
     */
    public function knockoutGames(): HasMany
    {
        return $this->hasMany(Game::class)->whereNotNull('stage')->orderBy('bracket_slot');
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
