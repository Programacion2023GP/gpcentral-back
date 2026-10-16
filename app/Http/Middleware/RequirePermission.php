<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'No autenticado'], 401);
        if (!$user->hasPermission($permission)) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }
        return $next($request);
    }
}
