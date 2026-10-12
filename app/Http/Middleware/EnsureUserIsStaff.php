<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('staff') for technicians and admins, ->middleware('staff:admin') for admins only. */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next, string $level = 'staff'): Response
    {
        $user = $request->user();

        abort_unless($level === 'admin' ? $user?->isAdmin() : $user?->isStaff(), 403);

        return $next($request);
    }
}
