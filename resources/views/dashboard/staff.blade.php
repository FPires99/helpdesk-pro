@php $max = max(1, $perDay->max('count')); $catMax = max(1, $byCategory->max('tickets_count')); @endphp

<x-app-layout title="Dashboard">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
            <p class="mt-1 text-sm text-slate-500">Here's what's happening on the service desk today.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Active tickets" :value="$activeCount" :href="route('tickets.index', ['status' => 'active'])" hint="Open, in progress or waiting" />
        <x-stat-card label="Unassigned" :value="$unassignedCount" :tone="$unassignedCount ? 'amber' : 'slate'" :href="route('tickets.index', ['view' => 'unassigned'])" hint="Need an owner" />
        <x-stat-card label="Overdue" :value="$overdueCount" :tone="$overdueCount ? 'red' : 'slate'" hint="Past their target time" />
        <x-stat-card label="Resolved this week" :value="$resolvedThisWeek" tone="emerald" :hint="$avgHours !== null ? 'Avg. '.$avgHours.'h to resolve · '.$withinSla.'% on target (30 days)' : null" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- New tickets per day --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-baseline justify-between">
                <h2 class="font-semibold">New tickets</h2>
                <span class="text-xs text-slate-500">Last 14 days</span>
            </div>
            <div class="mt-6 flex h-44 items-end gap-1.5" role="img" aria-label="Bar chart of new tickets per day over the last 14 days">
                @foreach ($perDay as $day)
                    <div class="group relative flex h-full flex-1 flex-col justify-end">
                        <div class="rounded-t bg-indigo-500 transition group-hover:bg-indigo-600" style="height: {{ max(2, $day['count'] / $max * 100) }}%"></div>
                        <span class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-1.5 py-0.5 text-xs text-white opacity-0 transition group-hover:opacity-100">
                            {{ $day['count'] }} · {{ $day['date']->format('j M') }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs text-slate-400">
                <span>{{ $perDay->first()['date']->format('j M') }}</span>
                <span>Today</span>
            </div>
        </section>

        {{-- Active tickets by category --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Active by category</h2>
            <ul class="mt-5 space-y-3.5">
                @foreach ($byCategory as $category)
                    <li>
                        <a href="{{ route('tickets.index', ['status' => 'active', 'category' => $category->id]) }}" class="group block">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 group-hover:text-slate-900">{{ $category->name }}</span>
                                <span class="font-semibold">{{ $category->tickets_count }}</span>
                            </div>
                            <div class="mt-1.5 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $category->tickets_count / $catMax * 100 }}%"></div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">Needs attention</h2>
                <a href="{{ route('tickets.index', ['view' => 'unassigned']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($needsAttention as $ticket)
                    <x-ticket-row :ticket="$ticket" />
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Nothing unassigned or overdue. Nice work!</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">My queue</h2>
                <a href="{{ route('tickets.index', ['view' => 'mine', 'status' => 'active']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($myQueue as $ticket)
                    <x-ticket-row :ticket="$ticket" />
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">No tickets assigned to you. Pick one from the unassigned list.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
