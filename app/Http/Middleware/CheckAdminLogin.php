<?php

namespace App\Http\Middleware;

use App\Models\Whitelist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckAdminLogin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // If the current logged in user is not admin whitelisted, redirect to the login page.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $whitelist = Whitelist::where([
            ['eve_id', $user->eve_id],
            ['is_admin', TRUE],
        ])->first();

        Log::info($whitelist);

        if (!isset($whitelist)) {
            // Not an admin, check if they are a whitelisted manager.
            $whitelist = Whitelist::where([
                ['eve_id', $user->eve_id],
                ['is_admin', FALSE],
            ])->first();

            if (isset($whitelist)) {
                return redirect('/timers');
            } else {
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
