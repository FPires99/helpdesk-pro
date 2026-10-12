<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // API: 60 requests a minute per user (or per IP when not signed in).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Ticket counts shown next to the sidebar links.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $active = Ticket::active()->visibleTo($user);

            $view->with('navCounts', [
                'active' => (clone $active)->count(),
                'mine' => $user->isStaff() ? (clone $active)->where('assignee_id', $user->id)->count() : null,
                'unassigned' => $user->isStaff() ? (clone $active)->whereNull('assignee_id')->count() : null,
                'notifications' => $user->unreadNotifications()->count(),
            ]);
        });
    }
}
