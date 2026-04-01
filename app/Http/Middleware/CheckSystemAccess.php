<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSystemAccess;

class CheckSystemAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $systemCode  Código del sistema (ej: 'payroll')
     * @param  string|null  $requiredRole  Rol requerido (opcional)
     * @return mixed
     */
    // public function handle(Request $request, Closure $next, string $systemCode, ?string $requiredRole = null)
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'No autenticado'
    //         ], 401);
    //     }

    //     // Verificar acceso activo en la fecha actual
    //     $today = now()->toDateString();

    //     $hasAccess = UserSystemAccess::where('user_id', $user->id)
    //         ->whereHas('system', fn($q) => $q->where('code', $systemCode))
    //         ->where('start_date', '<=', $today)
    //         ->where(function ($q) use ($today) {
    //             $q->whereNull('end_date')->orWhere('end_date', '>', $today);
    //         })
    //         ->exists();

    //     if (!$hasAccess) {
    //         return response()->json([
    //             'message' => "No tienes acceso al sistema '{$systemCode}'"
    //         ], 403);
    //     }

    //     // Si se requiere un rol específico, verificarlo
    //     if ($requiredRole) {
    //         $role = UserSystemAccess::where('user_id', $user->id)
    //             ->whereHas('system', fn($q) => $q->where('code', $systemCode))
    //             ->where('start_date', '<=', $today)
    //             ->where(function ($q) use ($today) {
    //                 $q->whereNull('end_date')->orWhere('end_date', '>', $today);
    //             })
    //             ->value('role');

    //         if ($role !== $requiredRole) {
    //             return response()->json([
    //                 'message' => "Se requiere el rol '{$requiredRole}' para acceder"
    //             ], 403);
    //         }
    //     }

    //     return $next($request);
    // }


    public function handle(Request $request, Closure $next, string $systemCode, ?string $requiredRole = null)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!$user->hasAccessToSystem($systemCode)) {
            return response()->json(['message' => "No tienes acceso al sistema '{$systemCode}'"], 403);
        }

        if ($requiredRole && $user->roleInSystem($systemCode) !== $requiredRole) {
            return response()->json(['message' => "Se requiere el rol '{$requiredRole}'"], 403);
        }

        return $next($request);
    }
}