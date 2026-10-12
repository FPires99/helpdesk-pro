<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Attachment;
use App\Models\CannedResponse;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\TicketMetrics;
use App\Services\TicketNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only(['q', 'status', 'priority', 'category', 'view']);

        $tickets = $this->filteredQuery($request)
            ->with(['requester', 'assignee', 'category'])
            ->withCount(['comments' => fn ($q) => $user->isStaff() ? $q : $q->where('is_internal', false)])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    /** Export the tickets matching the current filters as CSV (opens in Excel). */
    public function export(Request $request): StreamedResponse
    {
        $tickets = $this->filteredQuery($request)->with(['requester', 'assignee', 'category'])->oldest()->get();

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Subject', 'Status', 'Priority', 'Category', 'Requester', 'Department', 'Assignee', 'Created', 'Resolved', 'Hours to resolve', 'Within target', 'Rating']);
            foreach ($tickets as $t) {
                fputcsv($out, [
                    $t->reference(),
                    self::csvSafe($t->subject),
                    $t->status->label(),
                    $t->priority->label(),
                    $t->category->name,
                    self::csvSafe($t->requester->name),
                    $t->requester->department,
                    $t->assignee?->name,
                    $t->created_at->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i'),
                    TicketMetrics::resolutionHours($t),
                    $t->resolved_at ? (TicketMetrics::withinTarget($t) ? 'Yes' : 'No') : null,
                    $t->rating,
                ]);
            }
            fclose($out);
        }, 'tickets-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stop spreadsheet apps treating user text as a formula (CSV injection). */
    private static function csvSafe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    private function filteredQuery(Request $request): Builder
    {
        $user = $request->user();
        $filters = $request->only(['q', 'status', 'priority', 'category', 'view']);

        return Ticket::query()
            ->visibleTo($user)
            ->when($filters['q'] ?? null, function ($query, string $search) {
                // "HD-0042" or "42" also finds the ticket by its number.
                $id = preg_match('/^(?:HD-)?0*(\d+)$/i', trim($search), $m) ? (int) $m[1] : null;
                $query->where(fn ($q) => $q
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->when($id, fn ($q) => $q->orWhere('id', $id)));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $status === 'active'
                ? $q->active()
                : $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category_id', $category))
            ->when($user->isStaff() && ($filters['view'] ?? null) === 'mine', fn ($q) => $q->where('assignee_id', $user->id))
            ->when($user->isStaff() && ($filters['view'] ?? null) === 'unassigned', fn ($q) => $q->whereNull('assignee_id')->active());
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketNotifier $notifier): RedirectResponse
    {
        $ticket = $request->user()->tickets()->create($request->safe()->except('attachments'));
        foreach ($request->file('attachments', []) as $file) {
            Attachment::store($file, $ticket, $request->user());
        }
        $notifier->created($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->reference()} created. The IT team has been notified.");
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['requester', 'assignee', 'category', 'comments.author', 'comments.attachments', 'events.actor', 'attachments']);

        $comments = $request->user()->isStaff()
            ? $ticket->comments
            : $ticket->comments->reject->is_internal;

        // Replies and changes in one chronological timeline ("raised" is shown in the header card).
        $timeline = $comments->toBase()
            ->concat($ticket->events->where('type', '!=', TicketEvent::CREATED))
            ->sortBy(fn ($item) => [$item->created_at->getTimestamp(), $item instanceof TicketEvent ? 0 : 1])
            ->values();

        return view('tickets.show', [
            'ticket' => $ticket,
            'timeline' => $timeline,
            'categories' => Category::orderBy('name')->get(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'staff' => User::staff()->active()->orderBy('name')->get(),
            'savedReplies' => $request->user()->isStaff()
                ? CannedResponse::orderBy('title')->get()->map(fn ($r) => ['title' => $r->title, 'body' => $r->fillFor($ticket, $request->user())])
                : collect(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketNotifier $notifier): RedirectResponse
    {
        $ticket->update($request->validated());
        $notifier->updated($ticket, $request->user());

        return back()->with('status', 'Ticket updated.');
    }

    /** Quick action for staff: take ownership of a ticket. */
    public function assignToMe(Request $request, Ticket $ticket, TicketNotifier $notifier): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $ticket->assignee()->associate($request->user());
        if ($ticket->status === TicketStatus::Open) {
            $ticket->status = TicketStatus::InProgress;
        }
        $ticket->save();
        $notifier->updated($ticket, $request->user());

        return back()->with('status', "You're now handling {$ticket->reference()}.");
    }

    public function rate(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('rate', $ticket);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'rating_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $ticket->update($data + ['rated_at' => now()]);
        $ticket->events()->create(['user_id' => $request->user()->id, 'type' => TicketEvent::RATED, 'to' => (string) $data['rating']]);

        return back()->with('status', 'Thanks for your feedback!');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $reference = $ticket->reference();
        $ticket->delete();

        return redirect()->route('tickets.index')->with('status', "Ticket {$reference} deleted.");
    }
}
