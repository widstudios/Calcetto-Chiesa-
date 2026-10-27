<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_coach', 'csi_registered_on'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_coach' => 'boolean',
            'csi_registered_on' => 'date',
        ];
    }

    /**
     * Squadre allenate da questo utente.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'coach_team');
    }

    /**
     * Scheda arbitro collegata a questo account (solo per il ruolo arbitro).
     *
     * @return HasOne<Referee, $this>
     */
    public function referee(): HasOne
    {
        return $this->hasOne(Referee::class);
    }

    /**
     * L'allenatore è tesserato al CSI.
     */
    public function isCsiRegistered(): bool
    {
        return $this->csi_registered_on !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isReferee(): bool
    {
        return $this->role === Role::Arbitro;
    }

    /**
     * Allena almeno una squadra: tutti gli allenatori e gli admin segnati anche come allenatori.
     */
    public function isCoach(): bool
    {
        return $this->role === Role::Allenatore || $this->is_coach;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true)
            || ($this->isCoach() && in_array(Role::Allenatore, $roles, true));
    }

    /**
     * Ruolo da mostrare, per esempio «Amministratore torneo e allenatore».
     */
    public function roleLabel(): string
    {
        return $this->isAdmin() && $this->is_coach ? $this->role->label().' e allenatore' : $this->role->label();
    }

    /**
     * Solo chi può allenare: allenatori e admin che sono anche allenatori.
     *
     * @param  Builder<User>  $query
     */
    public function scopeCoaches(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('role', Role::Allenatore)->orWhere('is_coach', true));
    }
}
