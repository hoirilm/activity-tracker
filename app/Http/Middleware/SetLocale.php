<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', 'id');

        if (!in_array($locale, ['id', 'en'])) {
            $locale = 'id';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
