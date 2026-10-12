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

    /** Defaults for new tickets (match the database defaults). */
    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
    ];

    protected $fillable = [
        'user_id',
        'assignee_id',
        'category_id',
        'subject',
        'description',
        'status',
        'priority',
        'resolved_at',
        'rating',
        'rating_comment',
        'rated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'rated_at' => 'datetime',
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

        // Remove uploaded files along with the ticket (the database rows cascade).
        static::deleting(function (Ticket $ticket) {
            $ticket->attachments->each->delete();
        });

        // Activity history.
        static::created(function (Ticket $ticket) {
            $ticket->events()->create(['user_id' => $ticket->user_id, 'type' => TicketEvent::CREATED]);
        });

        static::updated(function (Ticket $ticket) {
            foreach (TicketEvent::TRACKED as $column => $type) {
                if (! $ticket->wasChanged($column)) {
                    continue;
                }
                $ticket->events()->create([
                    'user_id' => auth()->id(),
                    'type' => $type,
                    'from' => self::rawValue($ticket->getOriginal($column)),
                    'to' => self::rawValue($ticket->getAttribute($column)),
                ]);
            }
        });
    }

    private static function rawValue(mixed $value): ?string
    {
        return $value instanceof \BackedEnum ? $value->value : ($value === null ? null : (string) $value);
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

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->oldest();
    }

    /** A reply from the requester moves the ticket back into the support queue. */
    public function registerReplyFrom(User $user): void
    {
        if ($user->id !== $this->user_id || $user->isStaff()) {
            return;
        }

        $next = match ($this->status) {
            TicketStatus::Waiting => TicketStatus::InProgress,
            TicketStatus::Resolved, TicketStatus::Closed => TicketStatus::Open,
            default => null,
        };

        if ($next) {
            $this->update(['status' => $next]);
        }
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
