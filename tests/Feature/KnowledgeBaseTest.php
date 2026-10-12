<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_everyone_can_read_published_articles_but_drafts_are_staff_only(): void
    {
        $published = Article::factory()->create(['title' => 'Fix the VPN']);
        $draft = Article::factory()->create(['title' => 'Secret draft', 'is_published' => false]);
        $employee = User::factory()->create();

        $this->actingAs($employee)->get('/kb')->assertSee('Fix the VPN')->assertDontSee('Secret draft');
        $this->actingAs($employee)->get(route('articles.show', $published))->assertOk();
        $this->actingAs($employee)->get(route('articles.show', $draft))->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())->get(route('articles.show', $draft))->assertOk();
    }

    public function test_only_staff_can_write_articles(): void
    {
        $data = ['title' => 'Reset your password', 'body' => '1. Go to the portal', 'is_published' => 1];

        $this->actingAs(User::factory()->create())->post('/kb', $data)->assertForbidden();

        $this->actingAs(User::factory()->technician()->create())->post('/kb', $data)
            ->assertRedirect(route('articles.show', 'reset-your-password'));
        $this->assertDatabaseHas('articles', ['slug' => 'reset-your-password']);
    }

    public function test_markdown_is_rendered_without_raw_html(): void
    {
        $article = Article::factory()->create(['body' => "## Steps\n\n**Bold** <script>alert(1)</script>"]);

        $this->actingAs(User::factory()->create())->get(route('articles.show', $article))
            ->assertSee('<h2>Steps</h2>', false)
            ->assertSee('<strong>Bold</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_suggestions_match_a_ticket_subject(): void
    {
        Article::factory()->create(['title' => 'Outlook keeps asking for your password', 'body' => 'Clear saved credentials.']);
        Article::factory()->create(['title' => 'Add an office printer', 'body' => 'Settings, printers.']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('articles.suggest', ['q' => 'Outlook password prompt every 5 minutes']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Outlook keeps asking for your password');
    }

    public function test_helpful_votes_and_views_count_once_per_session(): void
    {
        $article = Article::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('articles.show', $article));
        $this->actingAs($user)->get(route('articles.show', $article));
        $this->actingAs($user)->post(route('articles.helpful', $article));
        $this->actingAs($user)->post(route('articles.helpful', $article));

        $article->refresh();
        $this->assertSame(1, $article->views);
        $this->assertSame(1, $article->helpful_count);
    }

    public function test_slugs_stay_unique(): void
    {
        $a = Article::factory()->create(['title' => 'VPN help']);
        $b = Article::factory()->create(['title' => 'VPN help']);

        $this->assertSame('vpn-help', $a->slug);
        $this->assertSame('vpn-help-2', $b->slug);
    }
}
