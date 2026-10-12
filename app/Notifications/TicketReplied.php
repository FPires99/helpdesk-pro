<?php

namespace App\Notifications;

use App\Models\Comment;

/** Sent to the other side of the conversation when someone posts a public reply. */
class TicketReplied extends TicketNotification
{
    public function __construct(public Comment $comment)
    {
        parent::__construct($comment->ticket);
    }

    protected function headline(): string
    {
        return "{$this->comment->author->name} replied to ticket {$this->ticket->reference()}";
    }

    protected function body(): ?string
    {
        return $this->comment->body;
    }
}
