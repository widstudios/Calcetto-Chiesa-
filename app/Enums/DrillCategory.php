<?php

namespace App\Enums;

/**
 * Tipo di esercizio della libreria allenamenti, nell'ordine in cui si mostra.
 */
enum DrillCategory: string
{
    case Riscaldamento = 'riscaldamento';
    case CambioDirezione = 'cambio_direzione';
    case Conduzione = 'conduzione';
    case Dominio = 'dominio';
    case Passaggio = 'passaggio';
    case Tiro = 'tiro';
    case Coordinazione = 'coordinazione';
    case UnoControUno = 'uno_contro_uno';
    case Giochi = 'giochi';
    case Portiere = 'portiere';

    public function label(): string
    {
        return match ($this) {
            self::Riscaldamento => 'Riscaldamento',
            self::CambioDirezione => 'Cambio di direzione',
            self::Conduzione => 'Conduzione palla',
            self::Dominio => 'Controllo e dominio palla',
            self::Passaggio => 'Passaggio e ricezione',
            self::Tiro => 'Tiro in porta',
            self::Coordinazione => 'Coordinazione e rapidità',
            self::UnoControUno => '1 contro 1 e superiorità',
            self::Giochi => 'Giochi',
            self::Portiere => 'Portiere',
        };
    }
}
