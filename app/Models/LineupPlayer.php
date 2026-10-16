<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un convocato nella distinta: maglia ed eventuale fascia da capitano.
 */
#[Fillable(['lineup_id', 'player_id', 'shirt_number', 'captain'])]
class LineupPlayer extends Model
{
    public const CAPTAIN = 'capitano';

    public const VICE = 'vice';

    protected function casts(): array
    {
        return [
            'shirt_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
