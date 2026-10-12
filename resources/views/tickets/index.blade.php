@php
    $isStaff = auth()->user()->isStaff();
    $heading = match (true) {
        ! $isStaff => 'My tickets',
        ($filters['view'] ?? null) === 'mine' => 'My queue',
        ($filters['view'] ?? null) === 'unassigned' => 'Unassigned tickets',
        default => 'All tickets',
    };
    $selectClass = 'rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<x-app-layout :title="$heading">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $heading }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $tickets->total() }} {{ Str::plural('ticket', $tickets->total()) }}</p>
        </div>
        <div class="flex gap-2">
            @if ($isStaff)
                <a href="{{ route('tickets.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50" title="Download these tickets as a CSV file">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export CSV
                </a>
            @endif
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New ticket
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mt-6 flex flex-wrap gap-3" x-data @change="$el.submit()">
        @if ($filters['view'] ?? null)
            <input type="hidden" name="view" value="{{ $filters['view'] }}">
        @endif
        <div class="relative min-w-[16rem] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by subject, description or HD-number…"
                   class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <select name="status" class="{{ $selectClass }}" aria-label="Status">
            <option value="">Any status</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active (not resolved)</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="priority" class="{{ $selectClass }}" aria-label="Priority">
            <option value="">Any priority</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
            @endforeach
        </select>
        <select name="category" class="{{ $selectClass }}" aria-label="Category">
            <option value="">Any category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @if (collect($filters)->except('view')->filter()->isNotEmpty())
            <a href="{{ route('tickets.index', array_filter(['view' => $filters['view'] ?? null])) }}" class="self-center text-sm font-medium text-slate-500 hover:text-slate-700">Clear filters</a>
        @endif
    </form>

    {{-- Ticket list --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @forelse ($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 transition last:border-0 hover:bg-slate-50 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-medium text-slate-500">{{ $ticket->reference() }}</span>
                        <x-status-badge :status="$ticket->status" />
                        <x-priority-badge :priority="$ticket->priority" />
                        @if ($isStaff && $ticket->isOverdue())
                            <span class="inline-flex items-center rounded-md bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Overdue</span>
                        @endif
                    </div>
                    <p class="mt-1.5 truncate font-semibold text-slate-900">{{ $ticket->subject }}</p>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $ticket->category->name }}
                        @if ($isStaff) · {{ $ticket->requester->name }} ({{ $ticket->requester->department }}) @endif
                        · opened {{ $ticket->created_at->diffForHumans() }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-4 text-sm text-slate-500">
                    @if ($ticket->comments_count)
                        <span class="inline-flex items-center gap-1" title="{{ $ticket->comments_count }} replies">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                            {{ $ticket->comments_count }}
                        </span>
                    @endif
                    @if ($ticket->assignee)
                        <x-avatar :user="$ticket->assignee" class="h-7 w-7" />
                    @else
                        <span class="rounded-full border border-dashed border-slate-300 px-2.5 py-1 text-xs">Unassigned</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <p class="mt-3 font-semibold">No tickets found</p>
                <p class="mt-1 text-sm text-slate-500">Try changing the filters, or raise a new ticket.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $tickets->links() }}</div>
</x-app-layout>
