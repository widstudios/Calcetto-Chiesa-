<?php

namespace App\Enums;

/**
 * Posizione del calciatore nella rosa: in prima squadra (titolare) o in panchina.
 */
enum SquadRole: string
{
    case Titolare = 'titolare';
    case Panchina = 'panchina';

    public function label(): string
    {
        return match ($this) {
            self::Titolare => 'Prima squadra',
            self::Panchina => 'Panchina',
        };
    }
}
