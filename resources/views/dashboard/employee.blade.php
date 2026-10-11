<x-app-layout title="Dashboard">
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 px-6 py-8 text-white shadow-sm sm:px-10">
        <h1 class="text-2xl font-bold tracking-tight">Hi {{ Str::before(auth()->user()->name, ' ') }}, how can IT help today?</h1>
        <p class="mt-2 max-w-xl text-indigo-100">Raise a ticket for anything from a forgotten password to a broken laptop. You'll get updates here as the team works on it.</p>
        <a href="{{ route('tickets.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Raise a ticket
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Open tickets" :value="$openCount" tone="indigo" :href="route('tickets.index', ['status' => 'active'])" />
        <x-stat-card label="Waiting on you" :value="$waitingCount" :tone="$waitingCount ? 'amber' : 'slate'" :href="route('tickets.index', ['status' => 'waiting'])" hint="IT needs a reply from you" />
        <x-stat-card label="Resolved" :value="$resolvedCount" tone="emerald" :href="route('tickets.index', ['status' => 'resolved'])" />
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold">Your recent tickets</h2>
            <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recent as $ticket)
                <x-ticket-row :ticket="$ticket" :show-requester="false" />
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">You haven't raised any tickets yet.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
