<?php

namespace App\Notifications;

use App\Enums\TicketStatus;

/** Sent to the requester when IT needs their reply, or when their ticket is resolved. */
class TicketStatusChanged extends TicketNotification
{
    protected function headline(): string
    {
        return match ($this->ticket->status) {
            TicketStatus::Waiting => 'IT needs a reply from you on your ticket',
            TicketStatus::Resolved => 'Your ticket has been resolved',
            default => 'Your ticket is now '.strtolower($this->ticket->status->label()),
        };
    }

    protected function body(): ?string
    {
        return $this->ticket->status === TicketStatus::Resolved
            ? 'If the problem comes back, just reply to the ticket and it will be reopened.'
            : null;
    }
}
