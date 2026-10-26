<?php

namespace App\Enums;

/**
 * Turno della fase finale a eliminazione diretta. Le partite di campionato o dei gironi non hanno turno.
 */
enum GameStage: string
{
    case Ottavi = 'ottavi';
    case Quarti = 'quarti';
    case Semifinale = 'semifinale';
    case Finale = 'finale';

    public function label(): string
    {
        return match ($this) {
            self::Ottavi => 'Ottavi di finale',
            self::Quarti => 'Quarti di finale',
            self::Semifinale => 'Semifinale',
            self::Finale => 'Finale',
        };
    }

    /** Turno con questo numero di partite (8 ottavi, 4 quarti, 2 semifinali, 1 finale). */
    public static function forGames(int $games): ?self
    {
        return match ($games) {
            8 => self::Ottavi,
            4 => self::Quarti,
            2 => self::Semifinale,
            1 => self::Finale,
            default => null,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Ottavi => self::Quarti,
            self::Quarti => self::Semifinale,
            self::Semifinale => self::Finale,
            self::Finale => null,
        };
    }
}
