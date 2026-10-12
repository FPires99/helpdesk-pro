<?php

namespace Tests\Feature;

use App\Models\CannedResponse;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CannedResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_staff_can_manage_saved_replies(): void
    {
        $this->actingAs(User::factory()->create())->get('/saved-replies')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/saved-replies', ['title' => 'x', 'body' => 'y'])->assertForbidden();

        $tech = User::factory()->technician()->create();
        $this->actingAs($tech)->post('/saved-replies', ['title' => 'Restart', 'body' => 'Please restart'])->assertRedirect();
        $this->actingAs($tech)->get('/saved-replies')->assertOk()->assertSee('Please restart');
    }

    public function test_placeholders_are_filled_in(): void
    {
        $requester = User::factory()->create(['name' => 'Jamie Walker']);
        $agent = User::factory()->technician()->create(['name' => 'Sam Patel']);
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $reply = CannedResponse::create(['title' => 't', 'body' => 'Hi {name}, {ticket} is fixed. {agent}']);

        $this->assertSame("Hi Jamie, {$ticket->reference()} is fixed. Sam Patel", $reply->fillFor($ticket, $agent));
    }

    public function test_saved_replies_appear_in_the_reply_box_for_staff_only(): void
    {
        CannedResponse::create(['title' => 'Need remote access', 'body' => 'Can I connect?']);
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->technician()->create())->get(route('tickets.show', $ticket))->assertSee('Need remote access');
        $this->actingAs($ticket->requester)->get(route('tickets.show', $ticket))->assertDontSee('Need remote access');
    }
}
