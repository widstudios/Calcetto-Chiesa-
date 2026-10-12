<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riga del tabellino: gol segnati e voto (pagella) di un calciatore in una partita.
 */
#[Fillable(['game_id', 'player_id', 'goals', 'rating'])]
class Performance extends Model
{
    protected function casts(): array
    {
        return [
            'goals' => 'integer',
            'rating' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
