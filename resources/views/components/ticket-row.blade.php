@props(['ticket', 'showRequester' => true])
<a href="{{ route('tickets.show', $ticket) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50">
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold">{{ $ticket->subject }}</p>
        <p class="mt-0.5 truncate text-xs text-slate-500">
            <span class="font-mono">{{ $ticket->reference() }}</span>
            @if ($showRequester) · {{ $ticket->requester->name }} @endif
            · {{ $ticket->created_at->diffForHumans() }}
        </p>
    </div>
    @if ($ticket->isOverdue() && auth()->user()->isStaff())
        <span class="rounded-md bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Overdue</span>
    @endif
    <x-priority-badge :priority="$ticket->priority" class="hidden sm:inline-flex" />
    <x-status-badge :status="$ticket->status" />
</a>
