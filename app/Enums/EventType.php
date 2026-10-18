<?php

namespace App\Enums;

/**
 * Tipi di evento libero del calendario (allenamenti e partite arrivano dalle loro tabelle).
 */
enum EventType: string
{
    case Riunione = 'riunione';
    case Genitori = 'genitori';
    case Formazione = 'formazione';
    case Sponsor = 'sponsor';
    case Altro = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::Riunione => 'Riunione',
            self::Genitori => 'Incontro genitori',
            self::Formazione => 'Formazione',
            self::Sponsor => 'Sponsor',
            self::Altro => 'Altro',
        };
    }
}
