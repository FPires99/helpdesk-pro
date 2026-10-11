<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ticket counts shown next to the sidebar links.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $active = Ticket::active()->visibleTo($user);

            $view->with('navCounts', [
                'active' => (clone $active)->count(),
                'mine' => $user->isStaff() ? (clone $active)->where('assignee_id', $user->id)->count() : null,
                'unassigned' => $user->isStaff() ? (clone $active)->whereNull('assignee_id')->count() : null,
            ]);
        });
    }
}
