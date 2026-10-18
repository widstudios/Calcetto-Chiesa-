<?php

namespace App\Enums;

/**
 * Tipi di evento del calendario.
 */
enum EventType: string
{
    case Allenamento = 'allenamento';
    case Partita = 'partita';
    case Riunione = 'riunione';
    case Genitori = 'genitori';
    case Formazione = 'formazione';
    case Sponsor = 'sponsor';
    case Altro = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::Allenamento => 'Allenamento',
            self::Partita => 'Partita',
            self::Riunione => 'Riunione',
            self::Genitori => 'Incontro genitori',
            self::Formazione => 'Formazione',
            self::Sponsor => 'Sponsor',
            self::Altro => 'Altro',
        };
    }
}
