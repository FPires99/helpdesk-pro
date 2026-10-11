@php
    $field = 'mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $hints = [
        'low' => 'Minor issue or request, no rush',
        'medium' => 'Affects my work but I can carry on',
        'high' => 'I can\'t do an important part of my job',
        'urgent' => 'Several people or customers are affected',
    ];
@endphp

<x-app-layout title="New ticket">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back to tickets</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight">Raise a new ticket</h1>
        <p class="mt-1 text-sm text-slate-500">Tell us what's wrong and the IT team will get back to you.</p>

        <form method="POST" action="{{ route('tickets.store') }}" class="mt-8 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div>
                <label for="subject" class="text-sm font-semibold">Subject</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="150" autofocus
                       placeholder="e.g. Outlook keeps asking for my password" class="{{ $field }}">
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <label for="category_id" class="text-sm font-semibold">Category</label>
                <select id="category_id" name="category_id" required class="{{ $field }}">
                    <option value="" disabled @selected(! old('category_id'))>Choose a category…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>

            <fieldset>
                <legend class="text-sm font-semibold">How urgent is it?</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach ($priorities as $priority)
                        <label class="relative flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3.5 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                            <input type="radio" name="priority" value="{{ $priority->value }}" class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('priority', 'medium') === $priority->value)>
                            <span>
                                <span class="block text-sm font-semibold">{{ $priority->label() }}</span>
                                <span class="block text-xs text-slate-500">{{ $hints[$priority->value] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
            </fieldset>

            <div>
                <label for="description" class="text-sm font-semibold">Description</label>
                <textarea id="description" name="description" rows="7" required maxlength="5000" class="{{ $field }}"
                          placeholder="What happened? When did it start? Any error messages? What have you already tried?">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                @csrf
                <a href="{{ route('tickets.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Submit ticket</button>
            </div>
        </form>
    </div>
</x-app-layout>
