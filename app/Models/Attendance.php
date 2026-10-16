<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Presenza o assenza di un calciatore all'allenamento di una certa data.
 */
#[Fillable(['team_id', 'player_id', 'date', 'present'])]
class Attendance extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'present' => 'boolean',
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
