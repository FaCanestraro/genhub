<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureClientAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_if(!$request->user()->is_client, 403);

        return $next($request);
    }
}
