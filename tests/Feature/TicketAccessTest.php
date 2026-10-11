<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
    }

    public function test_employee_can_raise_a_ticket(): void
    {
        $employee = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($employee)->post('/tickets', [
            'subject' => 'Printer offline',
            'category_id' => $category->id,
            'priority' => 'high',
            'description' => 'The printer shows offline.',
        ]);

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame($employee->id, $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
    }

    public function test_ticket_requires_valid_priority_and_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/tickets', ['subject' => 'x', 'category_id' => 999, 'priority' => 'whenever', 'description' => 'x'])
            ->assertSessionHasErrors(['category_id', 'priority']);
    }

    public function test_employee_only_sees_their_own_tickets(): void
    {
        $employee = User::factory()->create();
        $mine = Ticket::factory()->for($employee, 'requester')->create(['subject' => 'My laptop is slow']);
        $other = Ticket::factory()->create(['subject' => 'Someone else problem']);

        $this->actingAs($employee)->get('/tickets')
            ->assertOk()
            ->assertSee($mine->subject)
            ->assertDontSee($other->subject);

        $this->actingAs($employee)->get(route('tickets.show', $other))->assertForbidden();
    }

    public function test_staff_see_all_tickets(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)->get('/tickets')->assertOk()->assertSee($ticket->subject);
        $this->actingAs($tech)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_employee_cannot_change_status_or_assignee(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();

        $this->actingAs($employee)
            ->patch(route('tickets.update', $ticket), ['status' => 'closed'])
            ->assertForbidden();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_technician_can_update_and_resolving_sets_resolved_at(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)
            ->patch(route('tickets.update', $ticket), ['status' => 'resolved', 'assignee_id' => $tech->id])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertSame($tech->id, $ticket->assignee_id);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_tickets_cannot_be_assigned_to_employees(): void
    {
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)
            ->patch(route('tickets.update', $ticket), ['assignee_id' => $employee->id])
            ->assertSessionHasErrors('assignee_id');
    }

    public function test_assign_to_me_takes_ownership_and_starts_work(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)->post(route('tickets.assign-to-me', $ticket))->assertRedirect();

        $ticket->refresh();
        $this->assertSame($tech->id, $ticket->assignee_id);
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
    }

    public function test_internal_notes_are_hidden_from_the_requester(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();
        Comment::factory()->for($ticket)->for($tech, 'author')->create(['body' => 'Public reply text']);
        Comment::factory()->for($ticket)->for($tech, 'author')->create(['body' => 'Secret internal note', 'is_internal' => true]);

        $this->actingAs($employee)->get(route('tickets.show', $ticket))
            ->assertSee('Public reply text')
            ->assertDontSee('Secret internal note');

        $this->actingAs($tech)->get(route('tickets.show', $ticket))
            ->assertSee('Secret internal note');
    }

    public function test_employee_cannot_post_internal_notes(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();

        $this->actingAs($employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'hi', 'is_internal' => 1])
            ->assertForbidden();
    }

    public function test_requester_reply_moves_waiting_ticket_back_to_in_progress(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create(['status' => TicketStatus::Waiting]);

        $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), ['body' => 'I am at my desk now.']);

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    public function test_requester_reply_reopens_resolved_ticket(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create(['status' => TicketStatus::Resolved]);

        $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), ['body' => 'It broke again.']);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->resolved_at);
    }

    public function test_only_admins_can_delete_tickets(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->technician()->create())
            ->delete(route('tickets.destroy', $ticket))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertModelMissing($ticket);
    }

    public function test_overdue_ignores_tickets_waiting_on_the_user(): void
    {
        $old = ['priority' => 'urgent', 'created_at' => now()->subHours(5)];

        $this->assertTrue(Ticket::factory()->create($old + ['status' => TicketStatus::Open])->isOverdue());
        $this->assertFalse(Ticket::factory()->create($old + ['status' => TicketStatus::Waiting])->isOverdue());
        $this->assertFalse(Ticket::factory()->create(['priority' => 'low', 'created_at' => now()->subHours(5)])->isOverdue());
    }

    public function test_search_finds_ticket_by_reference(): void
    {
        $tech = User::factory()->technician()->create();
        $tickets = Ticket::factory()->count(3)->sequence(
            ['subject' => 'Alpha issue'], ['subject' => 'Beta issue'], ['subject' => 'Gamma issue'],
        )->create();

        $this->actingAs($tech)->get('/tickets?q='.$tickets[1]->reference())
            ->assertSee('Beta issue')
            ->assertDontSee('Alpha issue');
    }
}
