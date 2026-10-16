<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $team->isCoachedBy($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Team $team): bool
    {
        return false;
    }

    public function delete(User $user, Team $team): bool
    {
        return false;
    }

    /**
     * Inserire, modificare o togliere calciatori dalla rosa.
     */
    public function manageRoster(User $user, Team $team): bool
    {
        return $team->isCoachedBy($user);
    }
}
