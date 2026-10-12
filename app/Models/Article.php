<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A knowledge base article: a self-service fix for a common problem.
 */
class Article extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'user_id', 'title', 'slug', 'body', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            if ($article->isDirty('title') || ! $article->slug) {
                $article->slug = static::uniqueSlug($article->title, $article->id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Matches articles containing every meaningful word of the search, in the title or body. */
    public function scopeSearch(Builder $query, ?string $terms): void
    {
        foreach (static::keywords($terms) as $word) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$word}%")->orWhere('body', 'like', "%{$word}%"));
        }
    }

    /**
     * Best matches for free text such as a ticket subject: any keyword can match,
     * ranked by how many keywords appear (title matches count double).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function suggestFor(string $text, int $limit = 3): \Illuminate\Support\Collection
    {
        $words = static::keywords($text);
        if ($words->isEmpty()) {
            return collect();
        }

        return static::published()
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")->orWhere('body', 'like', "%{$word}%");
                }
            })
            ->get()
            ->map(function (self $article) use ($words) {
                $title = Str::lower($article->title);
                $body = Str::lower($article->body);
                $article->score = $words->sum(fn ($w) => (str_contains($title, $w) ? 2 : 0) + (str_contains($body, $w) ? 1 : 0));

                return $article;
            })
            ->sortByDesc(fn (self $a) => [$a->score, $a->helpful_count])
            ->values()
            // Drop weak matches: keep only articles scoring at least half as well as the best one.
            ->pipe(fn ($ranked) => $ranked->filter(fn (self $a) => $a->score >= max(2, ($ranked->first()->score ?? 0) / 2)))
            ->take($limit)
            ->values();
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private static function keywords(?string $text): \Illuminate\Support\Collection
    {
        $stopWords = ['the', 'and', 'for', 'not', 'can', 'cant', 'with', 'how', 'won', 'wont', 'doesn', 'isn', 'keeps', 'after', 'when', 'from', 'this', 'that', 'have', 'has', 'any', 'all', 'new', 'need', 'please', 'help', 'working', 'work', 'issue', 'problem', 'asking'];

        return collect(preg_split('/[\s\/]+/', Str::lower((string) $text)))
            ->map(fn ($w) => trim($w, '.,!?:;"\'()'))
            ->map(fn ($w) => preg_replace("/'.*$/", '', $w))
            ->filter(fn ($w) => mb_strlen($w) >= 3)
            ->reject(fn ($w) => in_array($w, $stopWords))
            ->unique()
            ->take(6)
            ->values();
    }

    /** The article body rendered from Markdown, with raw HTML stripped. */
    public function html(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function excerpt(int $length = 140): string
    {
        return Str::limit(trim(preg_replace('/[#*_>`\[\]()-]+/', ' ', strip_tags($this->body))), $length);
    }

    private static function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
