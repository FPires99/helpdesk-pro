<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketReplied;
use App\Notifications\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_new_ticket_notifies_active_staff_only(): void
    {
        $tech = User::factory()->technician()->create();
        $inactiveTech = User::factory()->technician()->create(['is_active' => false]);
        $otherEmployee = User::factory()->create();

        $this->actingAs(User::factory()->create())->post('/tickets', [
            'subject' => 'Help', 'category_id' => Category::factory()->create()->id,
            'priority' => 'low', 'description' => 'Something broke',
        ]);

        Notification::assertSentTo($tech, TicketCreated::class);
        Notification::assertNotSentTo([$inactiveTech, $otherEmployee], TicketCreated::class);
    }

    public function test_assignee_is_notified_unless_they_assigned_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->patch(route('tickets.update', $ticket), ['assignee_id' => $tech->id]);
        Notification::assertSentTo($tech, TicketAssigned::class);

        $other = Ticket::factory()->create();
        $this->actingAs($tech)->post(route('tickets.assign-to-me', $other));
        Notification::assertSentToTimes($tech, TicketAssigned::class, 1);
    }

    public function test_requester_hears_when_ticket_is_waiting_or_resolved(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)->patch(route('tickets.update', $ticket), ['status' => 'in_progress']);
        Notification::assertNothingSentTo($ticket->requester);

        $this->actingAs($tech)->patch(route('tickets.update', $ticket), ['status' => 'resolved']);
        Notification::assertSentTo($ticket->requester, TicketStatusChanged::class);
    }

    public function test_replies_go_to_the_other_side_and_internal_notes_go_nowhere(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['assignee_id' => $tech->id]);

        $this->actingAs($tech)->post(route('tickets.comments.store', $ticket), ['body' => 'Internal', 'is_internal' => 1]);
        Notification::assertNothingSent();

        $this->actingAs($tech)->post(route('tickets.comments.store', $ticket), ['body' => 'Try restarting']);
        Notification::assertSentTo($ticket->requester, TicketReplied::class);

        $this->actingAs($ticket->requester)->post(route('tickets.comments.store', $ticket), ['body' => 'Done, still broken']);
        Notification::assertSentTo($tech, TicketReplied::class);
    }

    public function test_notification_page_and_opening_marks_as_read(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();
        $employee->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => TicketStatusChanged::class,
            'data' => (new TicketStatusChanged($ticket))->toArray($employee),
        ]);

        $notification = $employee->notifications()->first();
        $this->actingAs($employee)->get('/notifications')->assertOk()->assertSee($ticket->reference());
        $this->actingAs($employee)->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('tickets.show', $ticket));
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
