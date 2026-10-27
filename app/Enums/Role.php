<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Allenatore = 'allenatore';
    // Accede solo per vedere le partite che arbitra, senza modificare nulla.
    case Arbitro = 'arbitro';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Amministratore torneo',
            self::Allenatore => 'Allenatore',
            self::Arbitro => 'Arbitro',
        };
    }
}
