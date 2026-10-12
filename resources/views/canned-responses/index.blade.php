@php $field = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

<x-app-layout title="Saved replies">
    <div class="mx-auto max-w-4xl">
        <h1 class="text-2xl font-bold tracking-tight">Saved replies</h1>
        <p class="mt-1 text-sm text-slate-500">Reusable answers for common requests. Insert them from the reply box on any ticket.</p>

        <div class="mt-8 grid gap-8 lg:grid-cols-5">
            <div class="space-y-3 lg:col-span-3">
                @forelse ($responses as $response)
                    <div x-data="{ editing: false }" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div x-show="!editing">
                            <div class="flex items-start justify-between gap-4">
                                <h2 class="font-semibold">{{ $response->title }}</h2>
                                <div class="flex shrink-0 gap-3 text-sm">
                                    <button @click="editing = true" class="font-medium text-slate-500 hover:text-slate-800">Edit</button>
                                    <form method="POST" action="{{ route('canned-responses.destroy', $response) }}" onsubmit="return confirm('Delete this saved reply?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-600 hover:text-red-500">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $response->body }}</p>
                            @if ($response->author)
                                <p class="mt-3 text-xs text-slate-400">Added by {{ $response->author->name }}</p>
                            @endif
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('canned-responses.update', $response) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <input name="title" value="{{ $response->title }}" required maxlength="80" class="{{ $field }}" aria-label="Title">
                            <textarea name="body" rows="5" required class="{{ $field }}" aria-label="Reply text">{{ $response->body }}</textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="editing = false" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No saved replies yet. Add your first one.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('canned-responses.store') }}" class="h-fit space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                @csrf
                <h2 class="font-semibold">Add a saved reply</h2>
                <div>
                    <label for="title" class="text-sm font-medium">Title</label>
                    <input id="title" name="title" value="{{ old('title') }}" required maxlength="80" placeholder="e.g. Restart and try again" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>
                <div>
                    <label for="body" class="text-sm font-medium">Reply</label>
                    <textarea id="body" name="body" rows="6" required class="{{ $field }}">{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                    <p class="font-semibold">Placeholders</p>
                    <ul class="mt-1 space-y-0.5">
                        @foreach (App\Models\CannedResponse::PLACEHOLDERS as $tag => $meaning)
                            <li><code class="rounded bg-white px-1 ring-1 ring-slate-200">{{ $tag }}</code> {{ $meaning }}</li>
                        @endforeach
                    </ul>
                </div>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Add reply</button>
            </form>
        </div>
    </div>
</x-app-layout>
