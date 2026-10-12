<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketMetrics;
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

        // Same figures as the Reports page for the last 30 days.
        $month = TicketMetrics::summarise(Ticket::where('created_at', '>=', now()->subDays(30)->startOfDay())->get());

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
            'resolvedLastWeek' => Ticket::where('resolved_at', '>=', now()->subDays(7))->count(),
            'month' => $month,
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
