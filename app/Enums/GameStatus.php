<?php

namespace App\Enums;

enum GameStatus: string
{
    case Programmata = 'programmata';
    case Giocata = 'giocata';
    case Rinviata = 'rinviata';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
