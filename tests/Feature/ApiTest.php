<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    public function test_a_real_token_works_end_to_end(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('script')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_employee_lists_only_their_tickets(): void
    {
        $employee = User::factory()->create();
        Ticket::factory()->for($employee, 'requester')->create(['subject' => 'Mine']);
        Ticket::factory()->create(['subject' => 'Not mine']);
        Sanctum::actingAs($employee);

        $this->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Mine');
    }

    public function test_create_ticket_and_validation_errors_are_json(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $this->postJson('/api/v1/tickets', ['subject' => 'Server CPU at 98%', 'category_id' => $category->id, 'priority' => 'urgent', 'description' => 'Alert from monitoring'])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'HD-0001')
            ->assertJsonPath('data.priority.value', 'urgent');

        $this->postJson('/api/v1/tickets', ['subject' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'category_id', 'priority', 'description']);
    }

    public function test_permissions_match_the_web_app(): void
    {
        $employee = User::factory()->create();
        $other = Ticket::factory()->create();
        $mine = Ticket::factory()->for($employee, 'requester')->create();
        Sanctum::actingAs($employee);

        $this->getJson("/api/v1/tickets/{$other->id}")->assertForbidden();
        $this->patchJson("/api/v1/tickets/{$mine->id}", ['status' => 'closed'])->assertForbidden();
    }

    public function test_internal_notes_are_hidden_from_employees(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();
        Comment::factory()->for($ticket)->create(['body' => 'Public', 'is_internal' => false]);
        Comment::factory()->for($ticket)->create(['body' => 'Secret', 'is_internal' => true]);

        Sanctum::actingAs($employee);
        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertJsonCount(1, 'data.comments')->assertJsonMissing(['body' => 'Secret']);

        Sanctum::actingAs(User::factory()->technician()->create());
        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertJsonCount(2, 'data.comments');
    }

    public function test_technician_can_update_and_comment(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();
        Sanctum::actingAs($tech);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", ['status' => 'in_progress', 'assignee_id' => $tech->id])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'in_progress')
            ->assertJsonPath('data.assignee.id', $tech->id);

        $this->postJson("/api/v1/tickets/{$ticket->id}/comments", ['body' => 'On it'])->assertCreated();
    }

    public function test_requester_reply_via_api_reopens_ticket(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create(['status' => TicketStatus::Resolved]);
        Sanctum::actingAs($employee);

        $this->postJson("/api/v1/tickets/{$ticket->id}/comments", ['body' => 'Broken again'])->assertCreated();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_api_is_rate_limited(): void
    {
        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/me')->assertOk();
        }
        $this->getJson('/api/v1/me')->assertTooManyRequests();
    }

    public function test_tokens_can_be_created_and_revoked_from_the_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('api-tokens.store'), ['token_name' => 'Monitoring'])
            ->assertSessionHas('new_api_token');
        $this->assertCount(1, $user->tokens);

        $this->actingAs($user)->delete(route('api-tokens.destroy', $user->tokens->first()->id));
        $this->assertCount(0, $user->fresh()->tokens);
    }
}
