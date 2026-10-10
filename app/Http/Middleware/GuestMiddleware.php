<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        if (Auth::check()) {

            return redirect('/Dashboard');
        }

        return $next($request);
    }
}
