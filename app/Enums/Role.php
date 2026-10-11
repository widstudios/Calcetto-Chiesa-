<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Allenatore = 'allenatore';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Amministratore torneo',
            self::Allenatore => 'Allenatore',
        };
    }
}
