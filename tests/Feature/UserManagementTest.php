<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_users(): void
    {
        $this->actingAs(User::factory()->technician()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
    }

    public function test_admin_adds_a_user_who_gets_a_set_password_email(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->admin()->create())->post('/admin/users', [
            'name' => 'New Starter', 'email' => 'new@helpdesk.test', 'role' => 'technician', 'department' => 'IT', 'is_active' => 1,
        ])->assertRedirect('/admin/users');

        $user = User::where('email', 'new@helpdesk.test')->firstOrFail();
        $this->assertSame(Role::Technician, $user->role);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_deactivated_users_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_users_are_signed_out_mid_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_successful_login_records_last_sign_in(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_deactivating_a_technician_returns_their_tickets_to_the_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['assignee_id' => $tech->id, 'status' => TicketStatus::InProgress]);
        $resolved = Ticket::factory()->create(['assignee_id' => $tech->id, 'status' => TicketStatus::Resolved]);

        $this->actingAs($admin)->patch(route('admin.users.update', $tech), [
            'name' => $tech->name, 'email' => $tech->email, 'role' => 'technician', 'department' => 'IT',
        ])->assertSessionHas('status', fn ($msg) => str_contains($msg, '1 open ticket returned to the queue'));

        $this->assertNull($ticket->fresh()->assignee_id);
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
        $this->assertSame($tech->id, $resolved->fresh()->assignee_id, 'History of finished tickets is kept');
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'employee', 'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }
}
