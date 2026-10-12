<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_rate_a_resolved_ticket_once(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        $this->actingAs($ticket->requester)
            ->post(route('tickets.rate', $ticket), ['rating' => 5, 'rating_comment' => 'Great'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame(5, $ticket->rating);
        $this->assertNotNull($ticket->rated_at);
        $this->assertDatabaseHas('ticket_events', ['ticket_id' => $ticket->id, 'type' => 'rated', 'to' => '5']);

        $this->actingAs($ticket->requester)->post(route('tickets.rate', $ticket), ['rating' => 1])->assertForbidden();
    }

    public function test_open_tickets_and_other_users_cannot_be_rated(): void
    {
        $open = Ticket::factory()->create();
        $this->actingAs($open->requester)->post(route('tickets.rate', $open), ['rating' => 4])->assertForbidden();

        $resolved = Ticket::factory()->create(['status' => TicketStatus::Resolved]);
        $this->actingAs(User::factory()->technician()->create())->post(route('tickets.rate', $resolved), ['rating' => 5])->assertForbidden();
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        $this->actingAs($ticket->requester)->post(route('tickets.rate', $ticket), ['rating' => 9])->assertSessionHasErrors('rating');
    }

    public function test_dashboard_shows_satisfaction(): void
    {
        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'rating' => 5, 'rated_at' => now()]);
        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'rating' => 3, 'rated_at' => now()]);

        $this->actingAs(User::factory()->technician()->create())->get('/dashboard')
            ->assertSee('4 / 5')
            ->assertSee('50% rated 4-5 stars');
    }
}
