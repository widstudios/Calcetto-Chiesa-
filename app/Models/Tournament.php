<?php

namespace App\Models;

use App\Enums\GameStage;
use App\Enums\TournamentFormat;
use App\Enums\TournamentRules;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'season', 'format', 'groups_count', 'qualifiers_per_group', 'two_leg_stages', 'starts_on', 'ends_on', 'lineup_max_players', 'rules_type', 'rules_text', 'rules_pdf', 'description'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    /** Massimo di gironi che si possono creare in un torneo. */
    public const MAX_GROUPS = 8;

    protected $attributes = ['format' => 'campionato', 'rules_type' => 'csi'];

    protected static function booted(): void
    {
        // Il PDF del regolamento sta in public/uploads: va via con il torneo.
        static::deleted(fn (Tournament $tournament) => $tournament->rules_pdf && Storage::disk('uploads')->delete($tournament->rules_pdf));
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'lineup_max_players' => 'integer',
            'format' => TournamentFormat::class,
            'rules_type' => TournamentRules::class,
            'groups_count' => 'integer',
            'qualifiers_per_group' => 'integer',
            'two_leg_stages' => 'array',
        ];
    }

    /** Il turno della fase finale si gioca con andata e ritorno? */
    public function isTwoLeg(GameStage $stage): bool
    {
        return in_array($stage->value, $this->two_leg_stages ?? [], true);
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
     * Il torneo segue un regolamento proprio invece di quello CSI.
     */
    public function hasOwnRules(): bool
    {
        return $this->rules_type === TournamentRules::Proprio;
    }

    /**
     * Indirizzo pubblico del PDF del regolamento proprio, se caricato.
     */
    public function rulesPdfUrl(): ?string
    {
        return $this->rules_pdf ? asset('uploads/'.$this->rules_pdf) : null;
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
