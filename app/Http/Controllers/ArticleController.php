<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $isStaff = $request->user()->isStaff();

        $articles = Article::query()
            ->with('category')
            ->unless($isStaff, fn ($q) => $q->published())
            ->search($request->query('q'))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderByDesc('helpful_count')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Article $article): View
    {
        Gate::authorize('view', $article);

        // Count each person once per session.
        $seen = $request->session()->get('kb_viewed', []);
        if (! in_array($article->id, $seen)) {
            $article->increment('views');
            $request->session()->push('kb_viewed', $article->id);
        }

        return view('articles.show', [
            'article' => $article->load('category', 'author'),
            'related' => Article::published()->whereKeyNot($article->id)
                ->where('category_id', $article->category_id)->take(3)->get(),
            'votedHelpful' => in_array($article->id, $request->session()->get('kb_helpful', [])),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);

        return view('articles.form', ['article' => new Article(['is_published' => true]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Article::class);

        $article = Article::create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->route('articles.show', $article)->with('status', 'Article published.');
    }

    public function edit(Article $article): View
    {
        Gate::authorize('update', $article);

        return view('articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);

        $article->update($request->validated());

        return redirect()->route('articles.show', $article)->with('status', 'Article updated.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return redirect()->route('articles.index')->with('status', 'Article deleted.');
    }

    /** "This solved my problem": counted once per session. */
    public function helpful(Request $request, Article $article): RedirectResponse
    {
        Gate::authorize('view', $article);

        if (! in_array($article->id, $request->session()->get('kb_helpful', []))) {
            $article->increment('helpful_count');
            $request->session()->push('kb_helpful', $article->id);
        }

        return back()->with('status', 'Glad it helped! Thanks for letting us know.');
    }

    /** Articles matching what someone is typing in a new ticket's subject. */
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->query('q');

        if (mb_strlen($query) < 4) {
            return response()->json([]);
        }

        return response()->json(
            Article::suggestFor($query)
                ->map(fn (Article $a) => [
                    'title' => $a->title,
                    'excerpt' => $a->excerpt(110),
                    'url' => route('articles.show', $a),
                ])
        );
    }
}
