<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\UserSystemAccess;
use Illuminate\Http\Request;

class UserSystemAccessController extends BaseCrudController
{
    protected $modelClass = UserSystemAccess::class;

    public function __construct()
    {
        $this->validationRules = [
            // 'employee_id' => 'required|exists:employees,id',
            'user_id' => 'required|exists:users,id',
            'system_id' => 'required|exists:systems,id',
            // 'role' => 'required|string|max:50',
            'start_date' => 'required|date',
        ];
    }

    public function verifyAccess(Request $request)
    {
        $systemCode = $request->query('system_code');
        if (!$systemCode) {
            return ObjResponse::error('Se requiere system_code', 400);
        }

        $user = Auth::user();
        $today = now()->toDateString();

        $access = UserSystemAccess::where('user_id', $user->id)
            ->whereHas('system', fn($q) => $q->where('code', $systemCode))
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $today);
            })
            ->first();

        return ObjResponse::success([
            'has_access' => (bool) $access,
            'role' => $access->role ?? null,
        ]);
    }

    // Opcional: si quieres listar solo los accesos del usuario autenticado
    public function myAccesses()
    {
        $userId = auth()->id();
        $accesses = UserSystemAccess::where('user_id', $userId)
            ->current()
            ->with('system')
            ->get();

        return ObjResponse::success($accesses, 'Mis accesos a sistemas');
    }

    // En UserSystemAccessController o en un controlador dedicado
    public function checkAccess(Request $request)
    {
        $user = auth()->user();
        $systemCode = $request->input('system_code');
        $date = $request->input('date', now()->toDateString());

        $hasAccess = $user->allSystemAccesses()
            ->whereHas('system', fn($q) => $q->where('code', $systemCode))
            ->activeAt($date)
            ->exists();

        $role = $user->roleInSystem($systemCode);

        return ObjResponse::success([
            'has_access' => $hasAccess,
            'role' => $role
        ]);
    }
}