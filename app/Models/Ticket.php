<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assignee_id',
        'category_id',
        'subject',
        'description',
        'status',
        'priority',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep resolved_at in sync with the status.
        static::saving(function (Ticket $ticket) {
            if (! $ticket->isDirty('status')) {
                return;
            }
            $ticket->resolved_at = $ticket->status->isActive() ? null : ($ticket->resolved_at ?? now());
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    /** Ticket number shown to users, e.g. HD-0042. */
    public function reference(): string
    {
        return 'HD-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** When the ticket should be resolved by, based on its priority. */
    public function dueAt(): Carbon
    {
        return $this->created_at->copy()->addHours($this->priority->slaHours());
    }

    /** The target-time clock is paused while the ticket waits on the requester. */
    public function isOverdue(): bool
    {
        return in_array($this->status, [TicketStatus::Open, TicketStatus::InProgress])
            && $this->dueAt()->isPast();
    }

    /** Limit the query to tickets the given user is allowed to see. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }
}
