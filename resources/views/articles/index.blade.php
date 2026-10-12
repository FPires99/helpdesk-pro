<x-app-layout title="Knowledge base">
    <div class="rounded-2xl bg-slate-900 px-6 py-10 text-center text-white sm:px-10">
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">How can we help?</h1>
        <p class="mt-2 text-slate-300">Step-by-step fixes for common IT problems, from the IT team.</p>
        <form method="GET" class="mx-auto mt-6 flex max-w-xl gap-2">
            <label for="kb-search" class="sr-only">Search articles</label>
            <input id="kb-search" type="search" name="q" value="{{ request('q') }}" placeholder="e.g. VPN, printer, password…"
                   class="flex-1 rounded-lg border-0 py-2.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-400">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <button class="rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-semibold hover:bg-indigo-400">Search</button>
        </form>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-2">
        <a href="{{ route('articles.index', array_filter(['q' => request('q')])) }}" @class(['rounded-full px-3 py-1.5 text-sm font-medium', 'bg-indigo-600 text-white' => ! request('category'), 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => request('category')])>All topics</a>
        @foreach ($categories as $category)
            <a href="{{ route('articles.index', array_filter(['q' => request('q'), 'category' => $category->id])) }}" @class(['rounded-full px-3 py-1.5 text-sm font-medium', 'bg-indigo-600 text-white' => request('category') == $category->id, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => request('category') != $category->id])>{{ $category->name }}</a>
        @endforeach
        @can('create', App\Models\Article::class)
            <a href="{{ route('articles.create') }}" class="ml-auto inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New article
            </a>
        @endcan
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($articles as $article)
            <a href="{{ route('articles.show', $article) }}" class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow">
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-indigo-600">{{ $article->category?->name ?? 'General' }}</span>
                    @unless ($article->is_published)
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">Draft</span>
                    @endunless
                </div>
                <h2 class="mt-2 font-semibold group-hover:text-indigo-700">{{ $article->title }}</h2>
                <p class="mt-2 flex-1 text-sm text-slate-500">{{ $article->excerpt() }}</p>
                <p class="mt-4 text-xs text-slate-400">
                    {{ $article->views }} {{ Str::plural('view', $article->views) }}
                    @if ($article->helpful_count) · helped {{ $article->helpful_count }} {{ Str::plural('person', $article->helpful_count) }} @endif
                </p>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center sm:col-span-2 lg:col-span-3">
                <p class="font-semibold">No articles found</p>
                <p class="mt-1 text-sm text-slate-500">Can't find a fix? <a href="{{ route('tickets.create') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Raise a ticket</a> and the IT team will help.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $articles->links() }}</div>
</x-app-layout>
