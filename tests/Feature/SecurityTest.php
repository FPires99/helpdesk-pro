<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the correct password is refused while locked out.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_demo_accounts_cannot_change_password_email_or_be_deleted(): void
    {
        config(['helpdesk.demo' => true]);
        $demo = User::factory()->create(['email' => 'employee@helpdesk.test']);

        $this->actingAs($demo)->patch('/profile', ['name' => 'Hacked', 'email' => 'hacked@example.com']);
        $this->actingAs($demo)->delete('/profile', ['password' => 'password']);
        $this->actingAs($demo)->put('/password', ['current_password' => 'password', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1']);

        $demo->refresh();
        $this->assertSame('employee@helpdesk.test', $demo->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $demo->password));
    }

    public function test_reset_command_refuses_outside_demo_mode(): void
    {
        config(['helpdesk.demo' => false]);

        $this->artisan('helpdesk:reset-demo --force')->assertFailed();
    }

    public function test_html_in_tickets_is_escaped(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = \App\Models\Ticket::factory()->create(['subject' => '<script>alert(1)</script>']);

        $this->actingAs($tech)->get(route('tickets.show', $ticket))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
