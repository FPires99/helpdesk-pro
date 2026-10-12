@php $field = 'mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

<x-app-layout :title="$user->exists ? 'Edit user' : 'Add user'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Users</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight">{{ $user->exists ? 'Edit '.$user->name : 'Add a user' }}</h1>
        @unless ($user->exists)
            <p class="mt-1 text-sm text-slate-500">They'll get an email with a link to choose their own password.</p>
        @endunless

        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mt-8 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="text-sm font-semibold">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="{{ $field }}">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <label for="email" class="text-sm font-semibold">Work email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $field }}">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <label for="role" class="text-sm font-semibold">Role</label>
                    <select id="role" name="role" class="{{ $field }}">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>
                <div>
                    <label for="department" class="text-sm font-semibold">Department</label>
                    <input id="department" name="department" value="{{ old('department', $user->department) }}" placeholder="e.g. Finance" class="{{ $field }}">
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm">
                    <span class="block font-semibold">Account active</span>
                    <span class="block text-slate-500">Untick for leavers. They're signed out immediately, can't sign in again, and any tickets assigned to them go back to the queue. Their ticket history is kept.</span>
                </span>
            </label>

            <div class="flex justify-end border-t border-slate-100 pt-6">
                @csrf
                @if ($user->exists)
                    @method('PATCH')
                @endif
                <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">{{ $user->exists ? 'Save changes' : 'Add user' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
