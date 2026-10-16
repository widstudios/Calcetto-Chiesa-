<?php

namespace App\Models;

use App\Enums\SquadRole;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['team_id', 'first_name', 'last_name', 'birth_year', 'phone', 'phone_2', 'shirt_number', 'squad_role'])]
class Player extends Model
{
    /** Cellulare valido: almeno 6 cifre, con spazi, punti, trattini o barre in mezzo e un + iniziale facoltativo. */
    public const PHONE_PATTERN = '/^\+?(?:[ .\/-]*\d){6,}[ .\/-]*$/';

    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'shirt_number' => 'integer',
            'squad_role' => SquadRole::class,
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
     * @return HasMany<Performance, $this>
     */
    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    /**
     * Partite in cui è stato il migliore in campo.
     *
     * @return HasMany<Game, $this>
     */
    public function mvpGames(): HasMany
    {
        return $this->hasMany(Game::class, 'mvp_player_id');
    }

    /**
     * Cellulari compilati, nell'ordine in cui sono salvati.
     *
     * @return list<string>
     */
    public function phones(): array
    {
        return array_values(array_filter([$this->phone, $this->phone_2]));
    }

    /**
     * Cifre di un numero per i link tel: ("333 123 4567" diventa "3331234567").
     */
    public static function dialable(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', $phone);
    }

    /**
     * Nome scritto come in anagrafica: "DE LUCA" diventa "De Luca", "D'ANGELO" diventa "D'Angelo".
     */
    public static function properName(string $value): string
    {
        $name = Str::title(Str::lower(preg_replace('/\s+/', ' ', trim($value))));

        return preg_replace_callback("/(?<=\p{L}')\p{Ll}/u", fn (array $m) => Str::upper($m[0]), $name);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
