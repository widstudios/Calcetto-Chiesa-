<?php

namespace App\Models;

use App\Enums\SquadRole;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'first_name', 'last_name', 'birth_year', 'shirt_number', 'squad_role'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'shirt_number' => 'integer',
            'squad_role' => SquadRole::class,
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
