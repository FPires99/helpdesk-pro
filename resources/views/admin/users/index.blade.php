@php $selectClass = 'rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

<x-app-layout title="Users">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Users</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} {{ Str::plural('account', $users->total()) }}</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add user
        </a>
    </div>

    <form method="GET" class="mt-6 flex flex-wrap gap-3" x-data @change="$el.submit()">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or department…" class="min-w-[16rem] flex-1 {{ $selectClass }}">
        <select name="role" class="{{ $selectClass }}" aria-label="Role">
            <option value="">Any role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <select name="status" class="{{ $selectClass }}" aria-label="Status">
            <option value="">Active and inactive</option>
            <option value="active" @selected(request('status') === 'active')>Active only</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Deactivated only</option>
        </select>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">User</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Department</th>
                    <th class="px-5 py-3">Tickets</th>
                    <th class="px-5 py-3">Last sign-in</th>
                    <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr @class(['opacity-60' => ! $user->is_active])>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$user" />
                                <div>
                                    <p class="font-semibold">{{ $user->name }}
                                        @unless ($user->is_active)
                                            <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-slate-600">Deactivated</span>
                                        @endunless
                                    </p>
                                    <p class="text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span @class(['rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                'bg-violet-50 text-violet-700 ring-violet-600/20' => $user->role === App\Enums\Role::Admin,
                                'bg-indigo-50 text-indigo-700 ring-indigo-600/20' => $user->role === App\Enums\Role::Technician,
                                'bg-slate-50 text-slate-600 ring-slate-500/20' => $user->role === App\Enums\Role::Employee])>{{ $user->role->label() }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $user->department ?? '–' }}</td>
                        <td class="px-5 py-3 text-slate-600">
                            {{ $user->tickets_count }} raised
                            @if ($user->isStaff()) · {{ $user->open_assigned_count }} assigned @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</x-app-layout>
