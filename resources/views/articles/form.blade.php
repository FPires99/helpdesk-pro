@php $field = 'mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

<x-app-layout :title="$article->exists ? 'Edit article' : 'New article'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ $article->exists ? route('articles.show', $article) : route('articles.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight">{{ $article->exists ? 'Edit article' : 'New knowledge base article' }}</h1>

        <form method="POST" action="{{ $article->exists ? route('articles.update', $article) : route('articles.store') }}" class="mt-8 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div>
                <label for="title" class="text-sm font-semibold">Title</label>
                <input id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="150" placeholder="e.g. How to connect to the VPN from home" class="{{ $field }}">
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div>
                <label for="category_id" class="text-sm font-semibold">Category</label>
                <select id="category_id" name="category_id" class="{{ $field }}">
                    <option value="">General</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $article->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="body" class="text-sm font-semibold">Content</label>
                <p class="mt-0.5 text-xs text-slate-500">Supports Markdown: <code>## Heading</code>, <code>**bold**</code>, <code>1.</code> numbered steps, <code>-</code> bullet points.</p>
                <textarea id="body" name="body" rows="16" required class="{{ $field }} font-mono">{{ old('body', $article->body) }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Published (visible to everyone). Untick to save as a draft only IT can see.
            </label>
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-6">
                @csrf
                @if ($article->exists)
                    @method('PATCH')
                @endif
                <span></span>
                <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">{{ $article->exists ? 'Save changes' : 'Publish article' }}</button>
            </div>
        </form>

        @if ($article->exists)
            <form method="POST" action="{{ route('articles.destroy', $article) }}" class="mt-4 text-right" x-data @submit="if (! confirm(@js('Delete this article?'))) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <button class="text-sm font-medium text-red-600 hover:text-red-500">Delete article</button>
            </form>
        @endif
    </div>
</x-app-layout>
