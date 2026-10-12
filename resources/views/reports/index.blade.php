@php
    $fmt = fn ($v, $suffix = '') => $v === null ? '–' : $v.$suffix;
    $th = 'px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500';
    $td = 'px-5 py-3';
    $bar = fn ($pct) => $pct === null ? '' : ($pct >= 80 ? 'text-emerald-700' : ($pct >= 60 ? 'text-amber-700' : 'text-red-600'));
@endphp

<x-app-layout title="Reports">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Reports</h1>
            <p class="mt-1 text-sm text-slate-500">Service desk performance for tickets raised in the selected period.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
                @foreach ($periods as $value => $label)
                    <a href="{{ route('reports', ['days' => $value]) }}" @class(['rounded-md px-3 py-1.5 font-medium', 'bg-indigo-600 text-white' => $days === $value, 'text-slate-600 hover:bg-slate-100' => $days !== $value])>{{ $label }}</a>
                @endforeach
            </div>
            <a href="{{ route('tickets.export') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export all tickets (CSV)
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card label="Tickets raised" :value="$summary['created']" />
        <x-stat-card label="Resolved" :value="$summary['resolved']" tone="emerald" />
        <x-stat-card label="Avg. time to resolve" :value="$fmt($summary['avg_hours'], 'h')" />
        <x-stat-card label="Resolved on target" :value="$fmt($summary['within_target'], '%')" tone="indigo" />
        <x-stat-card label="Satisfaction" :value="$fmt($summary['rating'], ' / 5')" tone="amber" :hint="$summary['ratings'].' ratings'" class="col-span-2 lg:col-span-1" />
    </div>

    <div class="mt-6 grid gap-6 2xl:grid-cols-2">
        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">By technician</h2>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="{{ $th }}">Technician</th><th class="{{ $th }}">Assigned</th><th class="{{ $th }}">Resolved</th><th class="{{ $th }}">Avg. time</th><th class="{{ $th }}">On target</th><th class="{{ $th }}">Rating</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($byTechnician as $row)
                        <tr>
                            <td class="{{ $td }}"><div class="flex items-center gap-2"><x-avatar :user="$row['user']" class="h-7 w-7" /><span class="font-medium">{{ $row['user']->name }}</span></div></td>
                            <td class="{{ $td }}">{{ $row['created'] }}</td>
                            <td class="{{ $td }}">{{ $row['resolved'] }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['avg_hours'], 'h') }}</td>
                            <td class="{{ $td }} font-semibold {{ $bar($row['within_target']) }}">{{ $fmt($row['within_target'], '%') }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['rating']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No assigned tickets in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">By category</h2>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="{{ $th }}">Category</th><th class="{{ $th }}">Raised</th><th class="{{ $th }}">Resolved</th><th class="{{ $th }}">Avg. time</th><th class="{{ $th }}">On target</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($byCategory as $row)
                        <tr>
                            <td class="{{ $td }} font-medium">{{ $row['label'] }}</td>
                            <td class="{{ $td }}">{{ $row['created'] }}</td>
                            <td class="{{ $td }}">{{ $row['resolved'] }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['avg_hours'], 'h') }}</td>
                            <td class="{{ $td }} font-semibold {{ $bar($row['within_target']) }}">{{ $fmt($row['within_target'], '%') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">By priority</h2>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="{{ $th }}">Priority</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">Raised</th><th class="{{ $th }}">Avg. time</th><th class="{{ $th }}">On target</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($byPriority as $row)
                        <tr>
                            <td class="{{ $td }}"><x-priority-badge :priority="$row['priority']" /></td>
                            <td class="{{ $td }} text-slate-500">{{ $row['priority']->slaHours() }}h</td>
                            <td class="{{ $td }}">{{ $row['created'] }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['avg_hours'], 'h') }}</td>
                            <td class="{{ $td }} font-semibold {{ $bar($row['within_target']) }}">{{ $fmt($row['within_target'], '%') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">By month</h2>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="{{ $th }}">Month</th><th class="{{ $th }}">Raised</th><th class="{{ $th }}">Resolved</th><th class="{{ $th }}">Avg. time</th><th class="{{ $th }}">On target</th><th class="{{ $th }}">Rating</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($byMonth as $row)
                        <tr>
                            <td class="{{ $td }} font-medium">{{ $row['label'] }}</td>
                            <td class="{{ $td }}">{{ $row['created'] }}</td>
                            <td class="{{ $td }}">{{ $row['resolved'] }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['avg_hours'], 'h') }}</td>
                            <td class="{{ $td }} font-semibold {{ $bar($row['within_target']) }}">{{ $fmt($row['within_target'], '%') }}</td>
                            <td class="{{ $td }}">{{ $fmt($row['rating']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
</x-app-layout>
