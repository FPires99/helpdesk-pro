<x-app-layout :title="$article->title">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('articles.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Knowledge base</a>

        <article class="mt-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-medium text-indigo-600">{{ $article->category?->name ?? 'General' }}</span>
                @unless ($article->is_published)
                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800">Draft: only IT can see this</span>
                @endunless
                @can('update', $article)
                    <a href="{{ route('articles.edit', $article) }}" class="ml-auto font-medium text-slate-500 hover:text-slate-800">Edit</a>
                @endcan
            </div>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $article->title }}</h1>
            <p class="mt-2 text-sm text-slate-500">Updated {{ $article->updated_at->format('j M Y') }}@if ($article->author) by {{ $article->author->name }}@endif</p>

            <div class="prose prose-slate mt-8 max-w-none prose-a:text-indigo-600">
                {!! $article->html() !!}
            </div>
        </article>

        <div class="mt-6 flex flex-col items-center gap-4 rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm sm:flex-row sm:text-left">
            <div class="flex-1">
                <p class="font-semibold">Did this fix your problem?</p>
                <p class="text-sm text-slate-500">If not, raise a ticket and the IT team will take it from here.</p>
            </div>
            <div class="flex gap-2">
                @if ($votedHelpful)
                    <span class="rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">Thanks for the feedback!</span>
                @else
                    <form method="POST" action="{{ route('articles.helpful', $article) }}">
                        @csrf
                        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Yes, it's fixed</button>
                    </form>
                @endif
                <a href="{{ route('tickets.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Raise a ticket</a>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <h2 class="mt-10 text-sm font-semibold uppercase tracking-wide text-slate-500">Related articles</h2>
            <div class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @foreach ($related as $item)
                    <a href="{{ route('articles.show', $item) }}" class="block px-5 py-3.5 text-sm font-medium hover:bg-slate-50">{{ $item->title }}</a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
