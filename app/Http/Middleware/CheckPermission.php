<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $menu, string $action)
    {
        $user = $request->user();

        if ($user->isOwner()) {
            return $next($request);
        }

        if (! ($user->role?->permissions[$menu][$action] ?? false)) {
            abort(403, 'Você não tem permissão para esta ação.');
        }

        return $next($request);
    }
}
