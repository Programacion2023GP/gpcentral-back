<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EmployeeAssignment;
use App\Models\ObjResponse;
use App\Models\Position;
use App\Models\VW_Department;
use App\Models\VW_Employee;
use App\Models\VW_User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepartmentController extends BaseCrudController
{
    protected $modelClass = Department::class;
    protected $modelClassView = VW_Department::class;
    protected $versioned = true;
    protected $imageDirectory = 'departments';
    protected $imageFields = ['seal_image'];
    protected $defaultOrderBy = ['id' => 'desc'];
    protected $useAuthFilter = true;
    protected $selectLabel = 'CONCAT(COALESCE(code, "")," - ",name)';

    public function __construct()
    {
        $this->validationRules = [
            'code'   => 'string|max:255',
            'name' => 'string|max:255',
        ];

        // $this->selectIndexQueryCallback = function ($query, Request $request) {
        //     $auth = Auth::user();
        //     if ($auth && $auth->role_id > 3) {
        //         $userEmployee = VW_User::where('id', $auth->id)->first();
        //         if ($userEmployee && !Str::contains($userEmployee->more_permissions ?? '', ['Ver Todas las Situaciones', 'todas'])) {
        //             $query->where('id', $userEmployee->department_id);
        //         }
        //     }
        // };
    }

    /**
     * Mostrar versión activa en una fecha.
     */
    public function show($id, Request $request,  $internal = false)
    {
        Log::info("Porque entre aqui al show?");
        try {
            $date = $request->get('date', now()->toDateString());
            $department = Department::where('id', $id)
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                })
                ->first();

            if (!$department) {
                return ObjResponse::notFound('Departamento no encontrado para la fecha especificada');
            }

            return ObjResponse::success($department);
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ show: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener departamento', $ex);
        }
    }

    public function directors(Request $request): JsonResponse
    {
        try {
            $department_uuid = $request->uuid;

            $positionsDirector = Position::select("uuid")
                ->where("active", true)
                ->where("name", "like", "%DIRECTOR%")
                ->where("name", "not like", "%SUB%DIRECTOR%")
                ->get();

            $history = EmployeeAssignment::where('department_uuid', $department_uuid)
                ->where("active", true)
                ->whereIn("position_uuid", $positionsDirector)
                ->whereNull('end_date')
                ->get();
            // $history = EmployeeAssignment::where('department_uuid', $department_uuid)->whereIn("position_uuid", $positionsDirector)->get();
            $data = [];

            $data = $history->map(function ($assignment) {
                $employee = VW_Employee::where("employee_id", $assignment->employee_id)->first();
                return [
                    'assignment_id' => $assignment->id,
                    'id' => $employee->employee_id,
                    'avatar' => $employee->avatar,
                    'signature_image' => $employee->signature_image,
                    'employee_id' => $employee->employee_id,
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee
                        ? trim("{$employee->full_name}")
                        : null,
                    'position_uuid' => $employee->position_uuid,
                    'position_name' => $employee->position_name ?? null,
                    'position_start' => $employee->position_start,
                    'position_end' => $employee->position_end,
                    'position_active' => $employee->position_active,
                ];
            });

            return ObjResponse::success($data, 'Historial de directores obtenido');
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ directors: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener historial de directores', $ex);
        }
    }
}
