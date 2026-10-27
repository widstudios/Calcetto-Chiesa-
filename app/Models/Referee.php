<?php

namespace App\Models;

use Database\Factories\RefereeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un arbitro delle partite. Il telefono si vede solo nel gestionale (admin).
 */
#[Fillable(['first_name', 'last_name', 'phone', 'user_id'])]
class Referee extends Model
{
    /** @use HasFactory<RefereeFactory> */
    use HasFactory;

    /**
     * Partite che arbitra.
     *
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Account con cui l'arbitro accede all'app per vedere le sue partite (facoltativo).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
