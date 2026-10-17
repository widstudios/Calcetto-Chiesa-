<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Impostazioni del sito (nome e logo) salvate dall'amministratore.
 */
class Setting extends Model
{
    /** Regolamento Oratorio Cup, art. 5.1: "La distinta può contenere fino a un massimo di 15 giocatori". */
    public const LINEUP_MAX_PLAYERS = 15;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /** Valori già letti in questa richiesta. */
    private static ?array $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::$cache = null);
        static::deleted(fn () => self::$cache = null);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            // Prima di "migrate" la tabella non c'è ancora: si usano i valori predefiniti.
            self::$cache = Schema::hasTable('settings') ? self::query()->pluck('value', 'key')->all() : [];
        }

        return self::$cache[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            self::query()->whereKey($key)->delete();
            self::$cache = null;
        } else {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /** Nome del sito: quello scelto nel pannello, altrimenti APP_NAME. */
    public static function siteName(): string
    {
        return self::get('site_name') ?? config('app.name');
    }

    /** Indirizzo del logo caricato, oppure null se si usa il pallone predefinito. */
    public static function logoUrl(): ?string
    {
        $path = self::get('logo');

        // asset() tiene conto dell'indirizzo con cui si apre il sito, anche se è in una sottocartella.
        return $path ? asset('uploads/'.$path) : null;
    }

    /** Massimo di giocatori in distinta per tutte le categorie che non ne hanno uno proprio. */
    public static function lineupMaxPlayers(): int
    {
        return (int) (self::get('lineup_max_players') ?? self::LINEUP_MAX_PLAYERS);
    }
}
