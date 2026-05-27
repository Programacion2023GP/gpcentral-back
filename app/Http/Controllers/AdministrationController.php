<?php

namespace App\Http\Controllers;

use App\Models\Administration;
use App\Models\ObjResponse;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdministrationController extends BaseCrudController
{
    protected $modelClass = Administration::class;
    protected $versioned = true;
    protected $imageDirectory = 'administrations';
    protected $imageFields = ['logo', 'logo_2', 'logo_3'];
    protected $validationRules = [
        'name' => 'required|string|max:255',
        'president_name' => 'required|string|max:255',
        'political_party' => 'nullable|string|max:255',
        'primary_color' => 'nullable|string|max:50',
        'secondary_color' => 'nullable|string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after:start_date',
    ];

    public function __construct()
    {
        $this->validationMessages = [
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];

        // Filtro para selectIndex según permisos
        $this->selectIndexQueryCallback = function ($query, Request $request) {
            $auth = Auth::user();
            if ($auth && $auth->role_id > 3) {
                // Ejemplo: si el usuario tiene un departamento asignado, filtrar
                $userEmployee = VW_User::where('id', $auth->id)->first();
                if ($userEmployee && !Str::contains($userEmployee->more_permissions ?? '', ['Ver Todas las Situaciones', 'todas'])) {
                    $query->where('id', $userEmployee->department_id);
                }
            }
        };
    }

}
