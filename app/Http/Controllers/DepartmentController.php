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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends BaseCrudController
{
    protected $modelClass = Department::class;
    protected $modelClassView = VW_Department::class;
    protected $versioned = true;
    protected $imageDirectory = 'departments';
    protected $imageFields = ['logo', 'seal_image'];
    protected $defaultOrderBy = ['id' => 'desc'];
    protected $useAuthFilter = true;
    protected $selectLabel = 'CONCAT(COALESCE(code, "")," - ",name)';

    public function __construct()
    {
        $this->validationRules = [
            'code'   => 'nullable|string|max:255|unique:departments,code,NULL,id|where:active,1|where:end_date,NULL',
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
            $departmentUuid = $request->department_uuid;

            $positionsDirector = Position::where("active", true)
                ->where("name", "like", "%DIRECTOR%")
                ->where("name", "not like", "%SUB%DIRECTOR%")
                ->pluck('uuid');

            $history = EmployeeAssignment::where('department_uuid', $departmentUuid)
                ->whereIn("position_uuid", $positionsDirector)
                ->select('employee_id', 'position_uuid', 'start_date', 'end_date', 'active', DB::raw('MAX(id) as id'), DB::raw('MAX(created_at) as created_at'))
                ->groupBy('employee_id', 'position_uuid', 'start_date', 'end_date', 'active')
                ->orderBy('created_at', 'desc')
                ->get();
            // Log::info($history);

            $data = $history->map(function ($assignment) {
                $employee = VW_Employee::where("employee_id", $assignment->employee_id)->first();
                return [
                    'id' => $assignment->id,
                    'assignment_id' => $assignment->id,
                    'director_avatar' => $employee->avatar,
                    'director_signature_image' => $employee->signature_image,
                    'director_employee_id' => $employee->employee_id,
                    'director_employee_code' => $employee->employee_code,
                    'director_name' => $employee
                        ? trim("{$employee->full_name}")
                        : null,
                    'position_uuid' => $employee->position_uuid,
                    'position_name' => $employee->position_name ?? null,
                    'assignment_start' => $assignment->start_date,
                    'assignment_end' => $assignment->end_date,
                    'assignment_active' => $assignment->active,
                ];
            });

            return ObjResponse::success($data, 'Historial de directores obtenido');
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ directors: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener historial de directores', $ex);
        }
    }
}