<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_ticket_records_an_event(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'type' => TicketEvent::CREATED,
        ]);
    }

    public function test_changes_are_recorded_with_who_made_them(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)->patch(route('tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $tech->id,
        ]);

        $events = $ticket->events()->where('type', '!=', TicketEvent::CREATED)->get()->keyBy('type');
        $this->assertCount(3, $events);
        $this->assertSame(['open', 'in_progress'], [$events['status']->from, $events['status']->to]);
        $this->assertSame(['medium', 'high'], [$events['priority']->from, $events['priority']->to]);
        $this->assertSame($tech->id, $events['assignee']->user_id);
        $this->assertSame('took ownership of this ticket', $events['assignee']->description());
    }

    public function test_saving_without_changes_records_nothing(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($tech)->patch(route('tickets.update', $ticket), ['status' => 'open', 'priority' => 'medium']);

        $this->assertSame(1, $ticket->events()->count());
    }

    public function test_history_appears_on_the_ticket_page(): void
    {
        $tech = User::factory()->technician()->create(['name' => 'Sam Patel']);
        $ticket = Ticket::factory()->create();
        $this->actingAs($tech)->patch(route('tickets.update', $ticket), ['status' => 'waiting']);

        $this->actingAs($ticket->requester)->get(route('tickets.show', $ticket))
            ->assertSee('Sam Patel')
            ->assertSee('changed the status from Open to Waiting on user');
    }
}
