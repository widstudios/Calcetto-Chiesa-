<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Document $document): bool
    {
        return $document->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Chi ha caricato il documento lo modifica o lo elimina; l'admin sempre.
     */
    public function update(User $user, Document $document): bool
    {
        return $document->user_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
