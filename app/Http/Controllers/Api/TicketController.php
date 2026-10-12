<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * JSON API (v1). Authenticate with a personal access token: `Authorization: Bearer <token>`.
 * Uses the same validation, permissions and notifications as the web app.
 */
class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->with(['category', 'requester', 'assignee'])
            ->when($request->query('status'), fn ($q, $status) => $status === 'active' ? $q->active() : $q->where('status', $status))
            ->when($request->query('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when($request->query('assignee') === 'me', fn ($q) => $q->where('assignee_id', $request->user()->id))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request, TicketNotifier $notifier): TicketResource
    {
        $ticket = $request->user()->tickets()->create($request->safe()->except('attachments'));
        $notifier->created($ticket);

        return new TicketResource($ticket->load(['category', 'requester']));
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['category', 'requester', 'assignee', 'comments' => fn ($q) => $q
            ->with('author')
            ->unless($request->user()->isStaff(), fn ($q) => $q->where('is_internal', false))]);

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketNotifier $notifier): TicketResource
    {
        $ticket->update($request->validated());
        $notifier->updated($ticket, $request->user());

        return new TicketResource($ticket->load(['category', 'requester', 'assignee']));
    }

    public function comment(StoreCommentRequest $request, Ticket $ticket, TicketNotifier $notifier): CommentResource
    {
        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_internal' => $request->boolean('is_internal'),
        ]);
        $ticket->registerReplyFrom($request->user());
        $notifier->replied($comment);

        return new CommentResource($comment->load('author'));
    }
}
