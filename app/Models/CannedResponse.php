<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved reply technicians can insert into a ticket.
 * Supports the placeholders {name}, {ticket} and {agent}.
 */
class CannedResponse extends Model
{
    public const PLACEHOLDERS = [
        '{name}' => "Requester's first name",
        '{ticket}' => 'Ticket number, e.g. HD-0042',
        '{agent}' => 'Your name',
    ];

    protected $fillable = ['user_id', 'title', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fillFor(Ticket $ticket, User $agent): string
    {
        return strtr($this->body, [
            '{name}' => strtok($ticket->requester->name, ' '),
            '{ticket}' => $ticket->reference(),
            '{agent}' => $agent->name,
        ]);
    }
}
