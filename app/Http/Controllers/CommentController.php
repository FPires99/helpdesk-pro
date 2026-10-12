<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Ticket;
use App\Services\TicketNotifier;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Ticket $ticket, TicketNotifier $notifier): RedirectResponse
    {
        $user = $request->user();
        $internal = $request->boolean('is_internal');

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $request->validated('body'),
            'is_internal' => $internal,
        ]);

        // A reply from the requester moves the ticket back into the support queue.
        if ($user->id === $ticket->user_id && ! $user->isStaff()) {
            $next = match ($ticket->status) {
                TicketStatus::Waiting => TicketStatus::InProgress,
                TicketStatus::Resolved, TicketStatus::Closed => TicketStatus::Open,
                default => null,
            };
            if ($next) {
                $ticket->update(['status' => $next]);
            }
        }

        $ticket->touch();
        $notifier->replied($comment);

        return redirect()
            ->to(route('tickets.show', $ticket).'#comments')
            ->with('status', $internal ? 'Internal note added.' : 'Reply posted.');
    }
}
