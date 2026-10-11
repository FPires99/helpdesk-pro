<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Waiting => 'Waiting on user',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /** Tailwind classes for the status badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Open => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::InProgress => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::Waiting => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::Resolved => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Closed => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::Waiting]);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return [self::Open, self::InProgress, self::Waiting];
    }
}
