<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for ticket notifications: sent by email (queued) and shown in the in-app bell.
 */
abstract class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
    }

    /** One-line summary, e.g. "Sam Patel replied to your ticket". */
    abstract protected function headline(): string;

    /** Optional extra text shown under the headline in the email. */
    protected function body(): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[{$this->ticket->reference()}] {$this->ticket->subject}")
            ->greeting('Hi '.strtok($notifiable->name, ' ').',')
            ->line($this->headline().':')
            ->line("**{$this->ticket->reference()}: {$this->ticket->subject}**");

        if ($body = $this->body()) {
            $mail->line($body);
        }

        return $mail->action('View ticket', route('tickets.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference(),
            'subject' => $this->ticket->subject,
            'message' => $this->headline(),
        ];
    }
}
