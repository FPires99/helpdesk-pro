<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the public demo, stop visitors changing the shared demo accounts' email or
 * password, or deleting them, so the one-click logins keep working for everyone.
 */
class ProtectDemoAccounts
{
    public function handle(Request $request, Closure $next): Response
    {
        $demoEmails = collect(config('helpdesk.demo_accounts'))->pluck('email');

        if (config('helpdesk.demo') && $demoEmails->contains($request->user()?->email)) {
            return back()->with('status', 'Demo accounts can\'t be changed, so the demo keeps working for everyone. Create your own account to try this.');
        }

        return $next($request);
    }
}
