<?php

namespace App\Enums;

/**
 * Regolamento del torneo: quello dell'Oratorio Cup del CSI Roma (già scritto sul sito) o uno proprio.
 */
enum TournamentRules: string
{
    case Csi = 'csi';
    case Proprio = 'proprio';

    public function label(): string
    {
        return match ($this) {
            self::Csi => 'Regolamento CSI',
            self::Proprio => 'Regolamento proprio',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Csi => 'Oratorio Cup 2026/2027 del CSI Roma, quello già pubblicato sul sito.',
            self::Proprio => 'Scrivi le regole del torneo e/o carica il PDF del regolamento.',
        };
    }
}
