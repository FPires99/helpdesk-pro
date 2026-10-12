<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Attachment::DISK);
    }

    public function test_files_can_be_attached_when_raising_a_ticket(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->post('/tickets', [
            'subject' => 'Error on startup',
            'category_id' => Category::factory()->create()->id,
            'priority' => 'medium',
            'description' => 'See screenshot',
            'attachments' => [UploadedFile::fake()->image('error.png'), UploadedFile::fake()->create('log.txt', 3, 'text/plain')],
        ])->assertSessionHasNoErrors();

        $ticket = Ticket::firstOrFail();
        $this->assertCount(2, $ticket->attachments);
        Storage::disk(Attachment::DISK)->assertExists($ticket->attachments->first()->path);
    }

    public function test_dangerous_or_huge_files_are_rejected(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();

        $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), [
            'body' => 'files',
            'attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
        ])->assertSessionHasErrors('attachments.0');

        $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), [
            'body' => 'files',
            'attachments' => [UploadedFile::fake()->image('huge.png')->size(6000)],
        ])->assertSessionHasErrors('attachments.0');
    }

    public function test_only_people_who_can_see_the_ticket_can_download(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();
        $attachment = Attachment::store(UploadedFile::fake()->image('error.png'), $ticket, $employee);

        $this->actingAs($employee)->get(route('attachments.show', $attachment))->assertOk();
        $this->actingAs(User::factory()->technician()->create())->get(route('attachments.show', $attachment))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('attachments.show', $attachment))->assertForbidden();
    }

    public function test_files_on_internal_notes_are_staff_only(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->for($employee, 'requester')->create();
        $note = Comment::factory()->for($ticket)->for($tech, 'author')->create(['is_internal' => true]);
        $attachment = Attachment::store(UploadedFile::fake()->image('logs.png'), $ticket, $tech, $note);

        $this->actingAs($employee)->get(route('attachments.show', $attachment))->assertForbidden();
        $this->actingAs($tech)->get(route('attachments.show', $attachment))->assertOk();
    }

    public function test_deleting_a_ticket_removes_its_files(): void
    {
        $ticket = Ticket::factory()->create();
        $attachment = Attachment::store(UploadedFile::fake()->image('error.png'), $ticket, $ticket->requester);

        $this->actingAs(User::factory()->admin()->create())->delete(route('tickets.destroy', $ticket));

        Storage::disk(Attachment::DISK)->assertMissing($attachment->path);
    }
}
