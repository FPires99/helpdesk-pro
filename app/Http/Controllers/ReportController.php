<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Performance reports for IT staff (routes are limited to staff). */
class ReportController extends Controller
{
    public const PERIODS = [30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'];

    public function __invoke(Request $request): View
    {
        $days = array_key_exists((int) $request->query('days'), self::PERIODS) ? (int) $request->query('days') : 30;

        $tickets = Ticket::with('assignee')->where('created_at', '>=', now()->subDays($days)->startOfDay())->get();

        $byMonth = $tickets->groupBy(fn (Ticket $t) => $t->created_at->format('Y-m'))->sortKeysDesc()
            ->map(fn ($group, $month) => ['label' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y')] + TicketMetrics::summarise($group));

        $byTechnician = User::staff()->orderBy('name')->get()
            ->map(fn (User $user) => ['user' => $user] + TicketMetrics::summarise($tickets->where('assignee_id', $user->id)))
            ->filter(fn ($row) => $row['created'] > 0);

        $byCategory = Category::orderBy('name')->get()
            ->map(fn (Category $c) => ['label' => $c->name, 'id' => $c->id] + TicketMetrics::summarise($tickets->where('category_id', $c->id)))
            ->sortByDesc('created');

        $byPriority = collect(TicketPriority::cases())
            ->map(fn (TicketPriority $p) => ['priority' => $p] + TicketMetrics::summarise($tickets->where('priority', $p)));

        return view('reports.index', [
            'days' => $days,
            'periods' => self::PERIODS,
            'summary' => TicketMetrics::summarise($tickets),
            'byMonth' => $byMonth,
            'byTechnician' => $byTechnician,
            'byCategory' => $byCategory,
            'byPriority' => $byPriority,
        ]);
    }
}
