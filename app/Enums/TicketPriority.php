<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Tailwind classes for the priority badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            self::Medium => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::High => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            self::Urgent => 'bg-red-50 text-red-700 ring-red-600/20',
        };
    }

    /** Target time to resolve, in hours (used for SLA indicators). */
    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Medium => 24,
            self::High => 8,
            self::Urgent => 2,
        };
    }
}
