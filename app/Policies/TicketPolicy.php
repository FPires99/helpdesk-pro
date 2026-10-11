<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Staff see every ticket; employees only their own. */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() || $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Changing status, priority, category or assignee is a staff job. */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /** Internal notes are only visible to, and writable by, staff. */
    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }
}
