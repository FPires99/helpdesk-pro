<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketReplied;
use App\Notifications\TicketStatusChanged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Decides who hears about what. The person who made a change is never notified about it.
 */
class TicketNotifier
{
    public function created(Ticket $ticket): void
    {
        $this->send(User::staff()->active()->get(), new TicketCreated($ticket), $ticket->requester);
    }

    /** Call after a staff member updates a ticket (status, assignee…). */
    public function updated(Ticket $ticket, User $actor): void
    {
        if ($ticket->wasChanged('assignee_id') && $ticket->assignee) {
            $this->send(collect([$ticket->assignee]), new TicketAssigned($ticket, $actor), $actor);
        }

        if ($ticket->wasChanged('status') && in_array($ticket->status, [TicketStatus::Waiting, TicketStatus::Resolved])) {
            $this->send(collect([$ticket->requester]), new TicketStatusChanged($ticket), $actor);
        }
    }

    public function replied(Comment $comment): void
    {
        if ($comment->is_internal) {
            return;
        }

        $ticket = $comment->ticket;
        $author = $comment->author;

        // Staff reply → requester. Requester reply → assignee, or the whole team if nobody owns it yet.
        $recipients = match (true) {
            $author->isStaff() => collect([$ticket->requester]),
            $ticket->assignee !== null => collect([$ticket->assignee]),
            default => User::staff()->active()->get(),
        };

        $this->send($recipients, new TicketReplied($comment), $author);
    }

    /** @param Collection<int, User> $recipients */
    private function send(Collection $recipients, object $notification, ?User $except): void
    {
        $recipients = $recipients->filter()->reject(fn (User $user) => $except && $user->is($except));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }
}
