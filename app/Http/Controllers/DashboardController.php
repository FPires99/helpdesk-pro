<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user->isStaff()) {
            $tickets = $user->tickets()->with(['category', 'assignee'])->latest()->get();

            return view('dashboard.employee', [
                'openCount' => $tickets->filter(fn ($t) => $t->status->isActive())->count(),
                'waitingCount' => $tickets->where('status', TicketStatus::Waiting)->count(),
                'resolvedCount' => $tickets->filter(fn ($t) => ! $t->status->isActive())->count(),
                'recent' => $tickets->take(6),
            ]);
        }

        $active = Ticket::active()->with(['requester', 'assignee', 'category'])->get();

        $resolvedRecently = Ticket::whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get(['created_at', 'resolved_at', 'priority']);

        $avgHours = $resolvedRecently->isEmpty() ? null
            : round($resolvedRecently->avg(fn ($t) => $t->created_at->diffInMinutes($t->resolved_at)) / 60, 1);

        $withinSla = $resolvedRecently->isEmpty() ? null
            : (int) round(100 * $resolvedRecently->filter(
                fn ($t) => $t->resolved_at->lte($t->created_at->copy()->addHours($t->priority->slaHours()))
            )->count() / $resolvedRecently->count());

        // Tickets created per day over the last 14 days.
        $since = now()->subDays(13)->startOfDay();
        $created = Ticket::where('created_at', '>=', $since)->pluck('created_at')
            ->countBy(fn (Carbon $date) => $date->toDateString());
        $perDay = collect(range(0, 13))->map(function (int $i) use ($since, $created) {
            $day = $since->copy()->addDays($i);

            return ['date' => $day, 'count' => $created[$day->toDateString()] ?? 0];
        });

        return view('dashboard.staff', [
            'activeCount' => $active->count(),
            'unassignedCount' => $active->whereNull('assignee_id')->count(),
            'overdueCount' => $active->filter->isOverdue()->count(),
            'resolvedThisWeek' => Ticket::where('resolved_at', '>=', now()->startOfWeek())->count(),
            'avgHours' => $avgHours,
            'withinSla' => $withinSla,
            'perDay' => $perDay,
            'byCategory' => Category::withCount(['tickets' => fn ($q) => $q->active()])
                ->orderByDesc('tickets_count')->get(),
            'myQueue' => $active->where('assignee_id', $user->id)
                ->sortBy(fn ($t) => $t->dueAt())->take(6),
            'needsAttention' => $active->filter(fn ($t) => $t->assignee_id === null || $t->isOverdue())
                ->sortBy(fn ($t) => $t->dueAt())->take(6),
        ]);
    }
}
