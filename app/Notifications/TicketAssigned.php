<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;

/** Sent to a technician when a ticket is assigned to them by someone else. */
class TicketAssigned extends TicketNotification
{
    public function __construct(Ticket $ticket, public User $assignedBy)
    {
        parent::__construct($ticket);
    }

    protected function headline(): string
    {
        return "{$this->assignedBy->name} assigned a ticket to you";
    }
}
