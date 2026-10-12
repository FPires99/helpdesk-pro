<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Attachment;
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
        foreach ($request->file('attachments', []) as $file) {
            Attachment::store($file, $ticket, $user, $comment);
        }

        $ticket->registerReplyFrom($user);
        $ticket->touch();
        $notifier->replied($comment);

        return redirect()
            ->to(route('tickets.show', $ticket).'#comments')
            ->with('status', $internal ? 'Internal note added.' : 'Reply posted.');
    }
}
