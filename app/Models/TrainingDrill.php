<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Esercizio fatto da una squadra nell'allenamento di una certa data.
 */
#[Fillable(['team_id', 'drill_id', 'date'])]
class TrainingDrill extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Drill, $this>
     */
    public function drill(): BelongsTo
    {
        return $this->belongsTo(Drill::class);
    }
}
