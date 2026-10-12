<?php

namespace App\Notifications;

/** Sent to IT staff when a new ticket is raised. */
class TicketCreated extends TicketNotification
{
    protected function headline(): string
    {
        return "{$this->ticket->requester->name} raised a new {$this->ticket->priority->label()} priority ticket";
    }

    protected function body(): ?string
    {
        return $this->ticket->description;
    }
}
