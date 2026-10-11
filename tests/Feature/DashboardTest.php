<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_renders_for_every_demo_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['employee@helpdesk.test', 'tech@helpdesk.test', 'admin@helpdesk.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $ticket = $user->isStaff() ? \App\Models\Ticket::first() : $user->tickets()->first();

            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->actingAs($user)->get('/tickets')->assertOk();
            $this->actingAs($user)->get('/tickets/create')->assertOk();
            $this->actingAs($user)->get(route('tickets.show', $ticket))->assertOk();
            $this->actingAs($user)->get('/profile')->assertOk();
        }
    }

    public function test_login_page_shows_demo_accounts(): void
    {
        $this->get('/login')->assertOk()->assertSee('Try the demo');
    }
}
