<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * L'allenatore crea eventi solo per le sue squadre (controllato nella validazione).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * L'allenatore modifica gli eventi delle sue squadre; quelli di tutta la società solo l'admin.
     */
    public function update(User $user, CalendarEvent $event): bool
    {
        return $event->team !== null && $event->team->isCoachedBy($user);
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $this->update($user, $event);
    }
}
