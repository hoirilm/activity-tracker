<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckWhitelistedEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $allowedEmails = explode(',', env('ALLOWED_EMAILS', ''));
            $allowedEmails = array_map('trim', $allowedEmails);

            if (!in_array(Auth::user()->email, $allowedEmails)) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Your email is not whitelisted to access this application.');
            }
        }

        return $next($request);
    }
}
