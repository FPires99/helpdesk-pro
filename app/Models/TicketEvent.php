<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a ticket's activity history, e.g. "status: open → in_progress".
 */
class TicketEvent extends Model
{
    public const CREATED = 'created';
    public const STATUS = 'status';
    public const PRIORITY = 'priority';
    public const ASSIGNEE = 'assignee';
    public const CATEGORY = 'category';
    public const RATED = 'rated';

    /** Ticket columns whose changes are recorded, and the event type for each. */
    public const TRACKED = [
        'status' => self::STATUS,
        'priority' => self::PRIORITY,
        'assignee_id' => self::ASSIGNEE,
        'category_id' => self::CATEGORY,
    ];

    protected $fillable = ['ticket_id', 'user_id', 'type', 'from', 'to'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Human-readable sentence, without the actor's name. */
    public function description(): string
    {
        return match ($this->type) {
            self::CREATED => 'raised this ticket',
            self::STATUS => 'changed the status from '.$this->label(TicketStatus::tryFrom((string) $this->from)).' to '.$this->label(TicketStatus::tryFrom((string) $this->to)),
            self::PRIORITY => 'changed the priority from '.$this->label(TicketPriority::tryFrom((string) $this->from)).' to '.$this->label(TicketPriority::tryFrom((string) $this->to)),
            self::ASSIGNEE => match (true) {
                $this->to === null => 'unassigned '.$this->name($this->from),
                (int) $this->to === $this->user_id => 'took ownership of this ticket',
                default => 'assigned this ticket to '.$this->name($this->to),
            },
            self::CATEGORY => 'moved this ticket from '.$this->categoryName($this->from).' to '.$this->categoryName($this->to),
            self::RATED => 'rated the support '.$this->to.'/5',
            default => 'updated this ticket',
        };
    }

    private function label(TicketStatus|TicketPriority|null $value): string
    {
        return $value?->label() ?? 'none';
    }

    private function name(?string $userId): string
    {
        return $userId ? (User::find($userId)?->name ?? 'a former user') : 'nobody';
    }

    private function categoryName(?string $categoryId): string
    {
        return $categoryId ? (Category::find($categoryId)?->name ?? 'a deleted category') : 'none';
    }
}
