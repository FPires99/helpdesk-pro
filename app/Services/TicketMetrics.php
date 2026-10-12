<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * Service-desk metrics for a set of tickets. Calculated in PHP so they work the same on SQLite and MySQL.
 */
class TicketMetrics
{
    /** @param Collection<int, Ticket> $tickets */
    public static function summarise(Collection $tickets): array
    {
        $resolved = $tickets->filter(fn (Ticket $t) => $t->resolved_at !== null);
        $rated = $tickets->whereNotNull('rating');

        return [
            'created' => $tickets->count(),
            'resolved' => $resolved->count(),
            'avg_hours' => $resolved->isEmpty() ? null
                : round($resolved->avg(fn (Ticket $t) => $t->created_at->diffInMinutes($t->resolved_at)) / 60, 1),
            'within_target' => $resolved->isEmpty() ? null
                : (int) round(100 * $resolved->filter(fn (Ticket $t) => self::withinTarget($t))->count() / $resolved->count()),
            'rating' => $rated->isEmpty() ? null : round($rated->avg('rating'), 1),
            'ratings' => $rated->count(),
            'happy' => $rated->isEmpty() ? null : (int) round(100 * $rated->filter(fn (Ticket $t) => $t->rating >= 4)->count() / $rated->count()),
        ];
    }

    public static function withinTarget(Ticket $ticket): bool
    {
        return $ticket->resolved_at !== null && $ticket->resolved_at->lte($ticket->dueAt());
    }

    public static function resolutionHours(Ticket $ticket): ?float
    {
        return $ticket->resolved_at ? round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 1) : null;
    }
}
