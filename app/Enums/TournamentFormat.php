<?php

namespace App\Enums;

/**
 * Formula del torneo: girone unico all'italiana (campionato) o più gironi con la loro classifica.
 */
enum TournamentFormat: string
{
    case Campionato = 'campionato';
    case Gironi = 'gironi';

    public function label(): string
    {
        return match ($this) {
            self::Campionato => 'Campionato',
            self::Gironi => 'A gironi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Campionato => 'Tutte le squadre in un\'unica classifica.',
            self::Gironi => 'Le squadre sono divise in gironi, ognuno con la sua classifica.',
        };
    }
}
