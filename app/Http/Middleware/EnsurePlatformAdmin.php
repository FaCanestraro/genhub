<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_if(!$request->user()->is_platform_admin, 403);

        return $next($request);
    }
}
